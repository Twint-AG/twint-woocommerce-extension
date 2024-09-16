<?php

declare(strict_types=1);

namespace Twint\Woo\Service;

use Exception;
use Throwable;
use Twint\Sdk\Value\Money;
use Twint\Sdk\Value\Order;
use Twint\Sdk\Value\OrderId;
use Twint\Sdk\Value\UnfiledMerchantTransactionReference;
use Twint\Sdk\Value\Uuid;
use Twint\Woo\Factory\ClientBuilder;
use Twint\Woo\Model\ApiResponse;
use Twint\Woo\Model\Pairing;
use Twint\Woo\Repository\PairingRepository;
use WC_Logger_Interface;
use WC_Order;

class PaymentService
{
    public function __construct(
        private readonly ClientBuilder $builder,
        private readonly ApiService $api,
        private readonly PairingRepository $repository,
        private readonly WC_Logger_Interface $logger
    ) {
    }

    /**
     * @throws Throwable
     */
    public function createOrder(WC_Order $order): ApiResponse
    {
        $client = $this->builder->build();

        try {
            $currency = $order->get_currency();
            $orderNumber = $order->get_order_number();

            return $this->api->call($client, 'startOrder', [
                new UnfiledMerchantTransactionReference($orderNumber),
                new Money($currency, (float) $order->get_total()),
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
//            dd($e);
            $this->logger->error(
                'TWINT PaymentService::createOrder error' . PHP_EOL . $e->getMessage()
            );
//            throw $e;
        }
    }

    /**
     * @throws Throwable
     */
    public function reverseOrder(WC_Order $order, float $amount, int $wcRefundId): ?ApiResponse
    {
        $client = $this->builder->build();

        try {
            $pairing = $this->repository->findByWooOrderId($order->get_id());
            if ($pairing instanceof Pairing) {
                $currency = $order->get_currency();
                if (!empty($currency) && $amount > 0) {
                    $reversalId = 'R-' . $pairing->getId() . '-' . $wcRefundId;
                    return $this->api->call($client, 'reverseOrder', [
                        new UnfiledMerchantTransactionReference($reversalId),
                        new OrderId(new Uuid($pairing->getId())),
                        new Money($currency, $amount),
                    ], false);
                }
            }
        } catch (Exception $e) {
            dd($e);
            $this->logger->error(
                'An error occurred during the communication with external payment gateway' . PHP_EOL . $e->getMessage()
            );

            throw $e;
        }

        return null;
    }
}
