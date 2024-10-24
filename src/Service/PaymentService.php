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
use Twint\Woo\Container\Lazy;
use Twint\Woo\Container\LazyLoadTrait;
use Twint\Woo\Factory\ClientBuilder;
use Twint\Woo\Model\ApiResponse;
use Twint\Woo\Model\Pairing;
use Twint\Woo\Model\TransactionLog;
use Twint\Woo\Repository\PairingRepository;
use WC_Logger_Interface;
use WC_Order;

/**
 * @method ClientBuilder getBuilder()
 * @method PairingRepository getRepository()
 */
class PaymentService
{
    use LazyLoadTrait;

    protected static array $lazyLoads = ['builder', 'repository'];

    public function __construct(
        private Lazy|ClientBuilder           $builder,
        private readonly ApiService          $api,
        private Lazy|PairingRepository       $repository,
        private readonly WC_Logger_Interface $logger
    ) {
    }

    /**
     * @throws Throwable
     */
    public function createOrder(WC_Order $order): ApiResponse
    {
        $client = $this->getBuilder()->build();

        try {
            $currency = $order->get_currency();
            $refId = $order->get_order_number() . '-' . wp_generate_password(4, false);

            return $this->api->call($client, 'startOrder', [
                new UnfiledMerchantTransactionReference($refId),
                new Money($currency, (float) $order->get_total()),
            ], true, static function (TransactionLog $log, mixed $return) use ($order) {
                if ($return instanceof Order) {
                    $log->setOrderId($order->get_id());
                }

                return $log;
            });
        } catch (Exception $e) {
            $this->logger->error('PaymentService::createOrder error' . PHP_EOL . $e->getMessage());
            throw $e;
        }
    }

    /**
     * @throws Throwable
     */
    public function reverseOrder(WC_Order $order, float $amount): ?ApiResponse
    {
        $client = $this->getBuilder()->build();

        $pairing = $this->getRepository()->get((string) $order->get_transaction_id());
        if (!$pairing instanceof Pairing) {
            $this->logger->error('Cannot refund due to non-exist pairing');
            return null;
        }

        $reversalId = 'R-' . $pairing->getId() . '-' . wp_generate_password(4, false);

        return $this->api->call($client, 'reverseOrder', [
            new UnfiledMerchantTransactionReference($reversalId),
            new OrderId(new Uuid($pairing->getId())),
            new Money(Money::CHF, $amount),
        ], true, static function (TransactionLog $log, mixed $return) use ($pairing, $order) {
            $log->setOrderId($order->get_id());
            $log->setPairingId($pairing->getId());

            return $log;
        });
    }
}
