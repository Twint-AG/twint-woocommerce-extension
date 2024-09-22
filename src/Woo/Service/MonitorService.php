<?php

declare(strict_types=1);

namespace Twint\Woo\Service;

use Exception;
use mysqli_sql_exception;
use Symfony\Component\Process\Process;
use Throwable;
use Twint\Command\PollCommand;
use Twint\Plugin;
use Twint\Sdk\InvocationRecorder\InvocationRecordingClient;
use Twint\Sdk\Value\FastCheckoutCheckIn;
use Twint\Sdk\Value\Money;
use Twint\Sdk\Value\Order;
use Twint\Sdk\Value\OrderId;
use Twint\Sdk\Value\PairingStatus;
use Twint\Sdk\Value\PairingUuid;
use Twint\Sdk\Value\UnfiledMerchantTransactionReference;
use Twint\Sdk\Value\Uuid;
use Twint\Sdk\Value\Version;
use Twint\Woo\Constant\TwintConstant;
use Twint\Woo\Exception\PaymentException;
use Twint\Woo\Factory\ClientBuilder;
use Twint\Woo\Model\ApiResponse;
use Twint\Woo\Model\Monitor\MonitoringStatus;
use Twint\Woo\Model\Pairing;
use Twint\Woo\Repository\PairingRepository;
use Twint\Woo\Repository\TransactionRepository;
use Twint\Woo\Service\Express\ExpressOrderService;
use WC_Logger_Interface;

class MonitorService
{
    public function __construct(
        private readonly PairingRepository $repository,
        private readonly TransactionRepository $logRepository,
        private readonly ClientBuilder $builder,
        private readonly WC_Logger_Interface $logger,
        private readonly PairingService $pairingService,
        private readonly ApiService $api,
        private readonly ExpressOrderService $orderService,
    ) {
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function monitors(): void
    {
        $pairings = $this->repository->loadInProcessPairings();

        /** @var Pairing $pairing */
        foreach ($pairings as $pairing) {
            try {
                $this->monitor($pairing);
            } catch (Throwable $e) {
                // Silent error to allow process handle next Pairings
                $this->logger->error("TWINT cli error: {$pairing->getId()} {$pairing->getToken()} {$e->getMessage()}");
            }
        }
    }

    /**
     * @throws Throwable
     */
    public function monitor(Pairing $pairing): MonitoringStatus
    {
        $cloned = clone $pairing;

        if ($pairing->getIsExpress()) {
            $status = $this->monitorExpress($pairing, $cloned);
            if ($status->paid()) {
                $this->repository->markAsOrdering($pairing->getId());
                try {
                    $this->orderService->setMonitor($this);
                    $this->orderService->update($cloned);
                    $status->addExtra('order', $pairing->getWcOrderId());

                    $this->repository->markAsPaid($pairing->getId());
                } catch (PaymentException $e) {
                    $this->logger->error('TWINT MonitorService::monitor: ' . $e->getMessage());
                    $this->repository->markAsCancelled($pairing->getId());
                }
            }

            return MonitoringStatus::fromPairing($pairing);
        }

        return $this->monitorRegular($pairing, $cloned);
    }

    /**
     * @throws Throwable
     */
    public function monitorRegular(Pairing $pairing, Pairing $cloned): MonitoringStatus
    {
        $client = $this->builder->build();

        try {
            $res = $this->api->call($client, 'monitorOrder', [new OrderId(new Uuid($pairing->getId()))], false);
        } catch (Throwable $e) {
            $this->logger->error('TWINT cannot get pairing status: ' . $e->getMessage());
            throw $e;
        }

        return $this->recursiveMonitor($pairing, $cloned, $client, $res);
    }

    /**
     * @throws Throwable
     */
    protected function recursiveMonitor(
        Pairing $orgPairing,
        Pairing $pairing,
        InvocationRecordingClient $client,
        ApiResponse $res
    ): MonitoringStatus {
        /** @var Order $tOrder */
        $tOrder = $res->getReturn();

        if ($pairing->hasDiffs($tOrder)) {
            try {
                $pairing = $this->pairingService->update($pairing, $res);
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() === TwintConstant::EXCEPTION_VERSION_CONFLICT) {
                    $this->logger->info("TWINT {$pairing->getId()} update was conflicted");
                    return MonitoringStatus::fromValues(false, MonitoringStatus::STATUS_IN_PROGRESS);
                }

                throw $e;
            }

            $log = $res->getLog();
            $log->setPairingId($pairing->getId());
            $log->setOrderId($pairing->getWcOrderId());
            $this->logRepository->save($log);
        }

        if ($tOrder->isPending()) {
            if ($tOrder->isConfirmationPending()) {
                $confirmRes = $this->api->call($client, 'confirmOrder', [
                    new UnfiledMerchantTransactionReference((string) $pairing->getWcOrderId()),
                    new Money(Money::CHF, $pairing->getAmount()),
                ]);

                return $this->recursiveMonitor($orgPairing, $pairing, $client, $confirmRes);
            }

            if ($orgPairing->isTimedOut()) {
                $cancellationRes = $this->cancelOrder($pairing, $client);

                return $this->recursiveMonitor($orgPairing, $pairing, $client, $cancellationRes);
            }

            return MonitoringStatus::fromValues(false, MonitoringStatus::STATUS_IN_PROGRESS);
        }

        /**
         * Only process as paid when:
         * - Did not process before (captured)
         * - First time get status success
         */
        if (!$orgPairing->isCaptured() && $tOrder->isSuccessful() && !$orgPairing->isSuccessful()) {
            $order = wc_get_order($pairing->getWcOrderId());

            // Mark the order as paid (completed)
            $order->payment_complete();

            // Optionally, add an order note
            $order->add_order_note('The order was marked as paid programmatically.');

            // Save changes
            $order->save();

            return MonitoringStatus::fromValues(true, MonitoringStatus::STATUS_PAID);
        }

        if ($tOrder->isFailure() && !$orgPairing->isFailure()) {
            if (!$orgPairing->isCaptured()) {
                $this->orderService->cancelOrder($pairing);
            }

            return MonitoringStatus::fromValues(true, MonitoringStatus::STATUS_CANCELLED);
        }

        return MonitoringStatus::fromValues(false, MonitoringStatus::STATUS_IN_PROGRESS);
    }

    /**
     * @throws Throwable
     */
    public function cancelOrder(Pairing $pairing, InvocationRecordingClient $client): ApiResponse
    {
        $this->logger->info("TWINT cancel order: {$pairing->getId()}");

        return $this->api->call($client, 'cancelOrder', [new OrderId(new Uuid($pairing->getId()))]);
    }

    /**
     * @throws Throwable
     */
    public function monitorExpress(Pairing $pairing, Pairing $cloned): MonitoringStatus
    {
        $client = $this->builder->build(Version::NEXT);

        $res = $this->api->call(
            $client,
            'monitorFastCheckOutCheckIn',
            [PairingUuid::fromString($cloned->getId())],
            false
        );

        return $this->monitorExpressRecursive($pairing, $cloned, $res, $client);
    }

    /**
     * @throws Throwable
     */
    public function monitorExpressRecursive(
        Pairing $pairing,
        Pairing $cloned,
        ApiResponse $res,
        InvocationRecordingClient $client
    ): MonitoringStatus {
        /** @var FastCheckoutCheckIn $state */
        $state = $res->getReturn();

        $status = MonitoringStatus::STATUS_IN_PROGRESS;
        $finished = false;

        if (!$cloned->hasDiffs($state)) {
            // Because cancelFastCheckoutCheckIn API return void then need monitor in next loop
            if ($state->pairingStatus()->__toString() === PairingStatus::PAIRING_IN_PROGRESS && $pairing->isTimedOut()) {
                $cancellationRes = $this->cancelFastCheckoutCheckIn($cloned, $client);
                $log = $cancellationRes->getLog();
                $this->logRepository->updatePartial($log, [
                    'pairing_id' => $cloned->getId(),
                    'order_id' => $cloned->getWcOrderId(),
                ]);
                $this->repository->markAsMerchantCancelled($cloned->getId());

                $cloned->setStatus(Pairing::EXPRESS_STATUS_MERCHANT_CANCELLED);
            }

            return MonitoringStatus::fromValues(false, MonitoringStatus::STATUS_IN_PROGRESS);
        }

        try {
            $cloned = $this->pairingService->updateForExpress($cloned, $state);
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === TwintConstant::EXCEPTION_VERSION_CONFLICT) {
                $this->logger->info("TWINT {$pairing->getId()} update was conflicted");
                return MonitoringStatus::fromValues(false, MonitoringStatus::STATUS_IN_PROGRESS);
            }

            throw $e;
        }

        $log = $res->getLog();
        $log->setOrderId($pairing->getWcOrderId());
        $log->setPairingId($pairing->getId());
        if ($log->isNewRecord()) {
            $this->logRepository->insert($log);
        } else {
            $this->logRepository->updatePartial($log, [
                'pairing_id' => $pairing->getId(),
                'order_id' => $pairing->getWcOrderId(),
            ]);
        }

        // As paid
        if ($pairing->getCustomerData() === [] && $state->hasCustomerData()) {
            $this->logger->info("TWINT paid {$pairing->getPairingStatus()} - {$cloned->getPairingStatus()}");
            $status = MonitoringStatus::STATUS_PAID;

            return MonitoringStatus::fromValues(true, $status, [
                'pairing' => $cloned,
            ]);
        }

        // As cancelled
        if (!$pairing->getIsOrdering() && $pairing->getPairingStatus() !== PairingStatus::NO_PAIRING && $cloned->getPairingStatus() === PairingStatus::NO_PAIRING && !$state->hasCustomerData()) {
            $this->logger->info(
                "TWINT mark as cancelled {$pairing->getPairingStatus()} - {$cloned->getPairingStatus()}"
            );

            $this->repository->markAsCancelled($pairing->getId());
            $finished = true;
            $status = MonitoringStatus::STATUS_CANCELLED;
        }

        return MonitoringStatus::fromValues($finished, $status);
    }

    /**
     * @throws Throwable
     */
    public function cancelFastCheckoutCheckIn(Pairing $pairing, InvocationRecordingClient $client): ApiResponse
    {
        $this->logger->info("TWINT cancel EC: {$pairing->getId()}");

        return $this->api->call($client, 'cancelFastCheckoutCheckIn', [
            PairingUuid::fromString($pairing->getId()),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function status(Pairing $pairing): MonitoringStatus
    {
        if ($pairing->isFinished()) {
            return MonitoringStatus::fromPairing($pairing);
        }

        if (!$pairing->isMonitoring()) {
            try {
                $process = new Process([
                    'php',
                    Plugin::abspath() . 'bin/console',
                    PollCommand::COMMAND,
                    $pairing->getId(),
                ]);
                $process->setOptions([
                    'create_new_console' => true,
                ]);
                $process->disableOutput();
                $process->start();
            } catch (Throwable $e) {
                $this->logger->error('TWINT error start monitor: ' . $e->getMessage());
                throw $e;
            }
        }

        return MonitoringStatus::fromPairing($pairing);
    }
}
