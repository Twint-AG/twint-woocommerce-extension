<?php

namespace Twint\Woo\Services;

use Twint\Sdk\Value\Money;
use Twint\Sdk\Value\NumericPairingToken;
use Twint\Sdk\Value\Order;
use Twint\Sdk\Value\OrderId;
use Twint\Sdk\Value\OrderStatus;
use Twint\Sdk\Value\UnfiledMerchantTransactionReference;
use Twint\Sdk\Value\Uuid;
use Twint\Woo\Exception\PaymentException;
use Twint\Woo\Factory\ClientBuilder;
use WC_Gateway_Twint_Regular_Checkout;
use WC_Order;
use function Psl\Type\string;

class PaymentService
{
    private ClientBuilder $client;

    public function __construct()
    {
        $this->client = new ClientBuilder();
    }

    /**
     * @param Order $twintOrder
     * @return array
     */
    public function parseTwintOrderToArray(Order $twintOrder): array
    {
        return [
            'id' => $twintOrder->id()
                ->__toString(),
            'status' => $twintOrder->status()
                ->__toString(),
            'transactionStatus' => $twintOrder->transactionStatus()
                ->__toString(),
            'pairingToken' => $twintOrder->pairingToken() instanceof NumericPairingToken ? $twintOrder->pairingToken()
                ->__toString() : '',
            'merchantTransactionReference' => $twintOrder->merchantTransactionReference()
                ->__toString(),
        ];
    }

    public function createOrder(WC_Order $order): void
    {
        $client = $this->client->build();
        try {
            $currency = $order->get_currency();
            $orderNumber = $order->get_order_number();
            $twintOrder = $client->startOrder(
                new UnfiledMerchantTransactionReference($orderNumber),
                new Money($currency, $order->get_total())
            );
            $twintApiArray = $this->parseTwintOrderToArray($twintOrder);
            $order->update_meta_data(
                'twint_api_response',
                json_encode($twintApiArray)
            );

            $order->save();

        } catch (\Exception $e) {
            wc_get_logger()->error('An error occurred during the communication with external payment gateway' . PHP_EOL . $e->getMessage());
            throw PaymentException::asyncProcessInterrupted(
                $order->get_transaction_id(),
                'An error occurred during the communication with external payment gateway' . PHP_EOL . $e->getMessage()
            );
        } finally {
            $invocations = $client->flushInvocations();
            $transactionLogService = new TransactionLogService();
            $transactionLogService->writeObjectLog(
                $order->get_id(),
                $order->get_status(),
                $order->get_transaction_id(),
                $invocations
            );
        }
    }

    /**
     * @throws \Exception|PaymentException
     */
    public function checkOrderStatus(WC_Order $order): ?Order
    {
        try {
            $currency = $order->get_currency();
            if (!$currency) {
                throw new \Exception('Missing currency for this order:' . $order->get_id() . PHP_EOL);
            }

            $twintApiResponse = json_decode($order->get_meta('twint_api_response'), true);
            if (empty($twintApiResponse) || empty($twintApiResponse['id'])) {
                throw new \Exception('Missing Twint response for this order:' . $order->get_id() . PHP_EOL);
            }

            $twintOrder = $this->client->build()->monitorOrder(new OrderId(new Uuid($twintApiResponse['id'])));

            if ($twintOrder->status()->equals(OrderStatus::SUCCESS())) {
                // TODO handle update paid payment order
                $order->update_status(WC_Gateway_Twint_Regular_Checkout::getOrderStatusAfterPaid());
            } elseif ($twintOrder->status()->equals(OrderStatus::FAILURE())) {
                // TODO Handle cancel order
                $msgNote = __('The order has bene cancelled.', 'woocommerce-gateway-twint');
                $order->update_status('cancelled', $msgNote);
            }
            return $twintOrder;
        } catch (\Exception $e) {
            throw PaymentException::asyncProcessInterrupted(
                $order->get_id(),
                'An error occurred during the communication with API gateway' . PHP_EOL . $e->getMessage()
            );
        } finally {
            $invocations = $this->client->build()->flushInvocations();
            $transactionLogService = new TransactionLogService();
            $transactionLogService->writeObjectLog(
                $order->get_id(),
                $order->get_status(),
                $order->get_transaction_id(),
                $invocations
            );
        }
    }

    /**
     * @param WC_Order $order
     * @param float $amount
     * @return Order|null
     */
    public function reverseOrder(WC_Order $order, float $amount): ?Order
    {
        $orderTransactionId = $order->get_transaction_id();
        $transactionLogService = new TransactionLogService();
        $client = $this->client->build();

        try {
            $twintApiResponse = json_decode($order->get_meta('twint_api_response'), true);
            if (!empty($twintApiResponse) && !empty($twintApiResponse['id'])) {
                $currency = $order->get_currency();
                if (!empty($currency) && $amount > 0) {
                    $invocations = $client->flushInvocations();
                    $transactionLogService->writeReverseOrderObjectLog(
                        $order->get_id(),
                        $order->get_status(),
                        $order->get_transaction_id(),
                        $invocations
                    );

                    $reversalIndex = $this->getReversalIndex($order->get_id());
                    $reversalId = 'R-' . $twintApiResponse['id'] . '-' . $reversalIndex;
                    $twintOrder = $client->reverseOrder(
                        new UnfiledMerchantTransactionReference($reversalId),
                        new OrderId(new Uuid($twintApiResponse['id'])),
                        new Money($currency, $amount)
                    );

                    if ($this->needUpdateStatusAfterRefunded($order, $amount)) {
                        $this->updateStatusAfterRefunded($order);
                    }

                    return $twintOrder;
                }
            }
            return null;
        } catch (\Exception $e) {
            throw PaymentException::asyncProcessInterrupted(
                $orderTransactionId,
                'An error occurred during the communication with API gateway' . PHP_EOL . $e->getMessage()
            );
        } finally {
            $invocations = $client->flushInvocations();
            $transactionLogService->writeReverseOrderObjectLog(
                $order->get_id(),
                $order->get_status(),
                $order->get_transaction_id(),
                $invocations
            );
        }
    }

    public function getPayLinks(string $token): array
    {
        $payLinks = [];
        try {
            $client = $this->client->build();
            $device = $client->detectDevice(string()->assert($_SERVER['HTTP_USER_AGENT'] ?? ''));
            if ($device->isAndroid()) {
                $payLinks['android'] = 'intent://payment#Intent;action=ch.twint.action.TWINT_PAYMENT;scheme=twint;S.code =' . $token . ';S.startingOrigin=EXTERNAL_WEB_BROWSER;S.browser_fallback_url=;end';
            } elseif ($device->isIos()) {
                $appList = [];
                $apps = $client->getIosAppSchemes();
                foreach ($apps as $app) {
                    $appList[] = [
                        'name' => $app->displayName(),
                        'link' => $app->scheme() . 'applinks/?al_applink_data={"app_action_type":"TWINT_PAYMENT","extras": {"code": "' . $token . '",},"referer_app_link": {"target_url": "", "url": "", "app_name": "EXTERNAL_WEB_BROWSER"}, "version": "6.0"}',
                    ];
                }
                $payLinks['ios'] = $appList;
            }
        } catch (\Exception $e) {
            return $payLinks;
        }
        return $payLinks;
    }

    public function getReversalIndex(string $orderId): int
    {
        // Get latest Increment ID and return it.
        $query_args = [
            'fields' => 'id=>parent',
            'post_type' => 'shop_order_refund',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'post_parent' => $orderId,
        ];

        $refunds = array_keys(get_posts($query_args));
        return intval($refunds[0]);
    }

    public function needUpdateStatusAfterRefunded(WC_Order $order, float|int $amount): bool
    {
        return true;
    }

    public function updateStatusAfterRefunded(WC_Order $order): bool
    {
        $remainingAmountRefunded = (float)$order->get_remaining_refund_amount();
        if ($remainingAmountRefunded > 0) {
            return $order->update_status('wc-refunded-partial');
        }

        return $order->update_status('wc-refunded');
    }
}