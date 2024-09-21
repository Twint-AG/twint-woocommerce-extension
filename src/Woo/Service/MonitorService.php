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
use Twint\Sdk\Value\PairingStatus;
use Twint\Sdk\Value\PairingUuid;
use Twint\Sdk\Value\Version;
use Twint\Woo\Constant\TwintConstant;
use Twint\Woo\Factory\ClientBuilder;
use Twint\Woo\Model\ApiResponse;
use Twint\Woo\Model\Monitor\MonitoringStatus;
use Twint\Woo\Model\Pairing;
use Twint\Woo\Repository\PairingRepository;
use Twint\Woo\Repository\TransactionRepository;
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

        return $pairing->getIsExpress() ? $this->monitorExpress($pairing, $cloned) : $this->monitorRegular(
            $pairing,
            $cloned
        );
    }

    public function monitorRegular(Pairing $pairing, Pairing $cloned): MonitoringStatus
    {
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
                    'order_id' => $cloned->getWcOrderId()
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

        $this->api->saveLog($res->getLog());

        // As paid
        if (($pairing->getCustomerData() === null || $pairing->getCustomerData() === '' || $pairing->getCustomerData() === '0') && $state->hasCustomerData()) {
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
