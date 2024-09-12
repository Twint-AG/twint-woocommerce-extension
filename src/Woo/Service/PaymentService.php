<?php

namespace Twint\Woo\Service;

use Exception;
use Throwable;
use Twint\Sdk\Value\Money;
use Twint\Sdk\Value\Order;
use Twint\Sdk\Value\OrderId;
use Twint\Sdk\Value\UnfiledMerchantTransactionReference;
use Twint\Sdk\Value\Uuid;
use Twint\Woo\Exception\PaymentException;
use Twint\Woo\Factory\ClientBuilder;
use Twint\Woo\Model\ApiResponse;
use Twint\Woo\Repository\PairingRepository;
use WC_Order;

class PaymentService
{
    public function __construct(
        private readonly ClientBuilder     $client = new ClientBuilder(),
        private readonly ApiService        $api = new ApiService(),
        private readonly PairingRepository $repository = new PairingRepository(),
    )
    {
    }

    /**
     * @param WC_Order $order
     * @return ApiResponse
     * @throws Throwable
     */
    public function createOrder(WC_Order $order): ApiResponse
    {
        $client = $this->client->build();
        try {
            $currency = $order->get_currency();
            $orderNumber = $order->get_order_number();

            return $this->api->call($client, 'startOrder', [
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

        } catch (Exception $e) {
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
     * @throws Throwable
     */
    public function reverseOrder(WC_Order $order, float $amount, int $wcRefundId): ?ApiResponse
    {
        $orderTransactionId = $order->get_transaction_id();
        $client = $this->client->build();

        try {
            $pairing = $this->repository->findByWooOrderId($order->get_id());
            if (!empty($pairing)) {
                $currency = $order->get_currency();
                if (!empty($currency) && $amount > 0) {
                    $reversalId = 'R-' . $pairing->getId() . '-' . $wcRefundId;
                    return $this->api->call($client, 'reverseOrder', [
                        new UnfiledMerchantTransactionReference($reversalId),
                        new OrderId(new Uuid($pairing->getId())),
                        new Money($currency, $amount)
                    ], false);
                }
            }
        } catch (Exception $e) {
            throw PaymentException::asyncProcessInterrupted(
                $orderTransactionId,
                'An error occurred during the communication with API gateway' . PHP_EOL . $e->getMessage()
            );
        }

        return null;
    }
}
