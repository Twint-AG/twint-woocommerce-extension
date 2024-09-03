<?php

namespace Twint\Woo\Services;

use Twint\Sdk\Value\Money;
use Twint\Sdk\Value\NumericPairingToken;
use Twint\Sdk\Value\Order;
use Twint\Sdk\Value\OrderId;
use Twint\Sdk\Value\OrderStatus;
use Twint\Sdk\Value\UnfiledMerchantTransactionReference;
use Twint\Sdk\Value\Uuid;
use Twint\Woo\Abstract\Core\Model\ApiResponse;
use Twint\Woo\App\API\ApiService;
use Twint\Woo\Exception\PaymentException;
use Twint\Woo\Factory\ClientBuilder;
use WC_Gateway_Twint_Regular_Checkout;
use WC_Order;
use function Psl\Type\string;

class PaymentService
{
    private ClientBuilder $client;
    private ApiService $apiService;
    private PairingService $pairingService;

    public function __construct()
    {
        $this->apiService = new ApiService();
        $this->client = new ClientBuilder();
        $this->pairingService = new PairingService();
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

    /**
     * @param WC_Order $order
     * @return ApiResponse
     * @throws \Throwable
     */
    public function createOrder(WC_Order $order): ApiResponse
    {
        $client = $this->client->build();
        try {
            $currency = $order->get_currency();
            $orderNumber = $order->get_order_number();

            return $this->apiService->call($client, 'startOrder', [
                new UnfiledMerchantTransactionReference($orderNumber),
                new Money($currency, $order->get_total()),
            ], true, static function (array $log, mixed $return) use ($order) {
                if ($return instanceof Order) {
                    $log['pairing_id'] = $return->id()->__toString();
                    $log['order_id'] = $order->get_id();
                    $log['order_status'] = $order->get_status();
                    $log['transaction_id'] = $order->get_transaction_id();
                }

                return $log;
            });

        } catch (\Exception $e) {
            wc_get_logger()->error('An error occurred during the communication with external payment gateway' . PHP_EOL . $e->getMessage());
            throw PaymentException::asyncProcessInterrupted(
                $order->get_transaction_id(),
                'An error occurred during the communication with external payment gateway' . PHP_EOL . $e->getMessage()
            );
        }
    }

    /**
     * @param WC_Order $order
     * @param float $amount
     * @param int $wcRefundId
     * @return ApiResponse|null
     * @throws \Throwable
     */
    public function reverseOrder(WC_Order $order, float $amount, int $wcRefundId): ?ApiResponse
    {
        $orderTransactionId = $order->get_transaction_id();
        $client = $this->client->build();

        try {
            $pairing = $this->pairingService->findByWooOrderId($order->get_id());
            if (!empty($pairing)) {
                $currency = $order->get_currency();
                if (!empty($currency) && $amount > 0) {
                    $reversalId = 'R-' . $pairing->getId() . '-' . $wcRefundId;
                    return $this->apiService->call($client, 'reverseOrder', [
                        new UnfiledMerchantTransactionReference($reversalId),
                        new OrderId(new Uuid($pairing->getId())),
                        new Money($currency, $amount)
                    ], false);
                }
            }
        } catch (\Exception $e) {
            throw PaymentException::asyncProcessInterrupted(
                $orderTransactionId,
                'An error occurred during the communication with API gateway' . PHP_EOL . $e->getMessage()
            );
        }

        return null;
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
}