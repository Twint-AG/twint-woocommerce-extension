<?php

declare(strict_types=1);

namespace Twint\Woo\Service;

use Exception;
use mysqli_sql_exception;
use Throwable;
use Twint\Sdk\Value\FastCheckoutCheckIn;
use Twint\Sdk\Value\InteractiveFastCheckoutCheckIn;
use Twint\Sdk\Value\Order;
use Twint\Sdk\Value\OrderId;
use Twint\Sdk\Value\PairingStatus;
use Twint\Sdk\Value\Uuid;
use Twint\Woo\Factory\ClientBuilder;
use Twint\Woo\Model\ApiResponse;
use Twint\Woo\Model\Gateway\RegularCheckoutGateway;
use Twint\Woo\Model\Pairing;
use Twint\Woo\Model\TransactionLog;
use Twint\Woo\Provider\DatabaseServiceProvider;
use Twint\Woo\Repository\PairingRepository;
use Twint\Woo\Repository\TransactionRepository;
use WC_Logger_Interface;
use WC_Order;

class PairingService
{
    public function __construct(
        private readonly PairingRepository $repository,
        private readonly TransactionRepository $logRepository,
        private readonly ClientBuilder $builder,
        private readonly ApiService $apiService,
        private readonly WC_Logger_Interface $logger
    ) {
    }

    /**
     * @throws Exception
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

        $pairing = $this->repository->save($pairing);

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
    public function monitor(Pairing $pairing): bool
    {
        if ($pairing->isFinished()) {
            return true;
        }

        $org = clone $pairing;

        $client = $this->builder->build();
        $apiResponse = $this->apiService->call($client, 'monitorOrder', [
            new OrderId(new Uuid($pairing->getId())),
        ], false);

        /** @var Order $tOrder */
        $tOrder = $apiResponse->getReturn();
        if ($pairing->getStatus() !== $tOrder->status()->__toString() ||
            $pairing->getPairingStatus() !== $tOrder->pairingStatus()?->__toString() ||
            $pairing->getTransactionStatus() !== $tOrder->transactionStatus()
                ->__toString()
        ) {
            try {
                $this->update($pairing, $apiResponse);
            } catch (mysqli_sql_exception $e) {
                if ($e->getSQLState() !== DatabaseServiceProvider::DATABASE_TRIGGER_STATE_ERROR_CODE) {
                    throw $e;
                }

                $this->logger->info(
                    "[TWINT] - Update pairing is locked {$pairing->getId()} {$pairing->getVersion()} {$pairing->getStatus()}"
                );

                return false;
            }
        }

        if ($tOrder->isPending()) {
            return false;
        }

        // Get WC Order
        $order = wc_get_order($pairing->getWcOrderId());

        if (!$org->isSuccess() && $tOrder->isSuccessful()) {
            $status = RegularCheckoutGateway::getOrderStatusAfterPaid();
            $this->logger->info("[TWINT] - Mark Order {$order->get_id()} and Pairing {$pairing->getId()} as {$status}");
            $order->update_status($status);
        }

        if (!$org->isFailed() && $tOrder->isFailure()) {
            $status = RegularCheckoutGateway::getOrderStatusAfterCancelled();
            $this->logger->info("[TWINT] - Mark Order {$order->get_id()} and Pairing {$pairing->getId()} as {$status}");
            $order->update_status($status);
        }

        $this->updateLog($apiResponse->getLog(), $pairing);

        return true;
    }

    public function update(Pairing $pairing, ApiResponse $response): Pairing
    {
        /** @var Order $order */
        $order = $response->getReturn();

        $pairing->setStatus($order->status()->__toString());
        $pairing->setPairingStatus($order->pairingStatus()?->__toString());
        $pairing->setTransactionStatus($order->transactionStatus()->__toString());

        return $this->repository->update($pairing);
    }

    /**
     * @throws Exception
     */
    protected function updateLog(TransactionLog $log, Pairing $pairing): void
    {
        $log->setPairingId($pairing->getId());

        $this->apiService->saveLog($log);
    }

    /**
     * @throws Exception
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

        $pairing = $this->repository->save($pairing);

        $log = $response->getLog();
        $log->setPairingId($pairing->getId());
        $this->logRepository->updatePartial($log, [
            'pairing_id' => $pairing->getId(),
            'order_id' => $pairing->getWcOrderId(),
        ]);

        return $pairing;
    }

    public function updateForExpress(Pairing $pairing, FastCheckoutCheckIn $checkIn): Pairing
    {
        $pairing->setVersion($pairing->getVersion());
        $pairing->setCustomerData($checkIn->hasCustomerData() ? json_encode($checkIn->customerData()) : null);
        $pairing->setShippingMethodId($checkIn->shippingMethodId()?->__toString() ?? null);
        $pairing->setPairingStatus((string) $checkIn->pairingStatus());

        $this->logger->info("TWINT update: {$pairing->getId()} {$pairing->getPairingStatus()}");

        return $this->repository->save($pairing);
    }
}
