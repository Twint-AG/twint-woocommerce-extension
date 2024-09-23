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
        private Lazy|ClientBuilder $builder,
        private readonly ApiService $api,
        private Lazy|PairingRepository $repository,
        private readonly WC_Logger_Interface $logger
    ) {
    }

    /**
     * @throws Throwable
     */
    public function createOrder(WC_Order $order): ApiResponse
    {
        $client = $this->getBuilder()
            ->build();

        try {
            $currency = $order->get_currency();
            $orderNumber = $order->get_order_number();

            return $this->api->call($client, 'startOrder', [
                new UnfiledMerchantTransactionReference($orderNumber),
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
    public function reverseOrder(WC_Order $order, float $amount, int $wcRefundId): ?ApiResponse
    {
        $client = $this->getBuilder()
            ->build();

        try {
            $pairing = $this->getRepository()
                ->findByWooOrderId($order->get_id());
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
            $this->logger->error('PaymentService::reverseOrder ' . PHP_EOL . $e->getMessage());

            throw $e;
        }

        return null;
    }
}
