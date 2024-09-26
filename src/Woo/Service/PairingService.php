<?php

declare(strict_types=1);

namespace Twint\Woo\Service;

use Exception;
use Throwable;
use Twint\Sdk\Value\FastCheckoutCheckIn;
use Twint\Sdk\Value\InteractiveFastCheckoutCheckIn;
use Twint\Sdk\Value\Order;
use Twint\Sdk\Value\PairingStatus;
use Twint\Woo\Container\Lazy;
use Twint\Woo\Container\LazyLoadTrait;
use Twint\Woo\Exception\DatabaseException;
use Twint\Woo\Factory\ClientBuilder;
use Twint\Woo\Model\ApiResponse;
use Twint\Woo\Model\Pairing;
use Twint\Woo\Repository\PairingRepository;
use Twint\Woo\Repository\TransactionRepository;
use WC_Logger_Interface;
use WC_Order;

/**
 * @method ClientBuilder getBuilder()
 * @method PairingRepository getRepository()
 */
class PairingService
{
    use LazyLoadTrait;

    protected static array $lazyLoads = ['builder', 'repository'];

    public function __construct(
        private Lazy|PairingRepository         $repository,
        private readonly TransactionRepository $logRepository,
        private Lazy|ClientBuilder             $builder,
        private readonly ApiService            $apiService,
        private readonly WC_Logger_Interface   $logger
    ) {
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function create(ApiResponse $response, WC_Order $order, bool $captured = false): Pairing
    {
        /** @var Order $tOrder */
        $tOrder = $response->getReturn();

        $pairing = new Pairing();

        $pairing->setToken($tOrder->pairingToken()?->__toString());
        $pairing->setAmount($tOrder->amount()->amount());
        $pairing->setWcOrderId($order->get_id());
        $pairing->setId($tOrder->id()->__toString());
        $pairing->setTransactionStatus($tOrder->transactionStatus()->__toString());
        $pairing->setPairingStatus($tOrder->pairingStatus()->__toString());
        $pairing->setStatus($tOrder->status()->__toString());
        $pairing->setCaptured($captured);

        $pairing = $this->getRepository()->save($pairing);

        $log = $response->getLog();
        $log->setPairingId($pairing->getId());
        $this->logRepository->updatePartial($log, [
            'pairing_id' => $pairing->getId(),
        ]);

        return $pairing;
    }

    /**
     * @throws Throwable
     */
    public function update(Pairing $pairing, ApiResponse $response): Pairing
    {
        /** @var Order $order */
        $order = $response->getReturn();

        $pairing->setStatus($order->status()->__toString());
        $pairing->setPairingStatus($order->pairingStatus()?->__toString());
        $pairing->setTransactionStatus($order->transactionStatus()->__toString());

        $this->logger->info(
            "TWINT update: {$pairing->getId()} {$pairing->getVersion()} {$pairing->getPairingStatus()} {$pairing->getTransactionStatus()}"
        );
        return $this->getRepository()->update($pairing);
    }

    /**
     * @throws Exception|Throwable
     */
    public function createExpressPairing(ApiResponse $response, WC_Order $order): Pairing
    {
        /** @var InteractiveFastCheckoutCheckIn $checkin */
        $checkin = $response->getReturn();

        $pairing = new Pairing();

        $pairing->setToken($checkin->pairingToken()->__toString());
        $pairing->setAmount((float) $order->get_total());
        $pairing->setWcOrderId($order->get_id());
        $pairing->setId($checkin->pairingUuid()->__toString());
        $pairing->setPairingStatus($checkin->pairingStatus()->__toString());
        $pairing->setStatus(PairingStatus::PAIRING_IN_PROGRESS);
        $pairing->setIsExpress(true);

        $pairing = $this->getRepository()->save($pairing);

        $log = $response->getLog();
        $log->setPairingId($pairing->getId());
        $this->logRepository->updatePartial($log, [
            'pairing_id' => $pairing->getId(),
            'order_id' => $pairing->getWcOrderId(),
        ]);

        return $pairing;
    }

    /**
     * @throws Throwable|DatabaseException
     */
    public function updateForExpress(Pairing $pairing, FastCheckoutCheckIn $checkIn): Pairing
    {
        $pairing->setCustomerData($checkIn->hasCustomerData() ? json_encode($checkIn->customerData()) : null);
        $pairing->setShippingMethodId($checkIn->shippingMethodId()?->__toString() ?? null);
        $pairing->setPairingStatus((string) $checkIn->pairingStatus());

        $this->logger->info("TWINT update: {$pairing->getId()} {$pairing->getPairingStatus()}");

        return $this->getRepository()->save($pairing);
    }
}
