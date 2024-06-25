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
use WC_Order;

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
            throw PaymentException::asyncProcessInterrupted(
                $order->get_transaction_id(),
                'An error occurred during the communication with external payment gateway' . PHP_EOL . $e->getMessage()
            );
        } finally {
            $innovations = $client->flushInvocations();
            $transactionLogService = new TransactionLogService();
            $transactionLogService->writeObjectLog(
                $order->get_id(),
                $order->get_status(),
                $order->get_transaction_id(),
                $innovations
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

            $referenceId = $order->get_id();
            if ($order->get_transaction_id()) {
                $referenceId = $order->get_transaction_id();
            }

            $twintApiResponse = json_decode($order->get_meta('twint_api_response'), true);
            if (empty($twintApiResponse) || empty($twintApiResponse['id'])) {
                throw new \Exception('Missing Twint response for this order:' . $order->get_id() . PHP_EOL);
            }

            $twintOrder = $this->client->build()->monitorOrder(new OrderId(new Uuid($twintApiResponse['id'])));
            $transactionId = $order->get_transaction_id();
            if ($transactionId === null) {
                throw new \Exception('Missing transaction id for this order:' . $referenceId . PHP_EOL);
            }

            if ($twintOrder->status()->equals(OrderStatus::SUCCESS())) {
                // TODO handle update paid payment order
                return $this->client->build()->confirmOrder(
                    new OrderId(new Uuid($twintApiResponse['id'])),
                    new Money($currency, $order->get_total())
                );
            } elseif ($twintOrder->status()->equals(OrderStatus::FAILURE())) {
                // TODO Handle cancel order
            }
            return $twintOrder;
        } catch (\Exception $e) {
            throw PaymentException::asyncProcessInterrupted(
                $order->get_id(),
                'An error occurred during the communication with API gateway' . PHP_EOL . $e->getMessage()
            );
        } finally {
            // TODO handle logger
        }
    }

    /**
     * @throws PaymentException
     */
    public function reverseOrder(WC_Order $order): ?Order
    {
        $orderTransactionId = $order->get_transaction_id();
        try {
            $twintApiResponse = json_decode($order->get_meta('twint_api_response'), true);
            if (!empty($twintApiResponse) && !empty($twintApiResponse['id'])) {
                $orderTransactionId = $order->get_transaction_id();
                $currency = $order->get_currency();
                if ($orderTransactionId && !empty($currency) && $order->get_total() > 0) {
                    $client = $this->client->build();
                    $twintOrder = $client->monitorOrder(new OrderId(new Uuid($twintApiResponse['id'])));
                    if ($twintOrder->status()->equals(OrderStatus::SUCCESS())) {
                        $twintOrder = $client->reverseOrder(
                            new UnfiledMerchantTransactionReference('R-' . $twintApiResponse['id']),
                            new OrderId(new Uuid($twintApiResponse['id'])),
                            new Money($currency, $order->get_total())
                        );
                        // TODO Handle refund
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
            // TODO handle logger
        }
    }
}