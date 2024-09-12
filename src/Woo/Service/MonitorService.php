<?php

namespace Twint\Woo\Service;

use Exception;
use Throwable;
use Twint\Woo\Model\Pairing;
use Twint\Woo\Repository\PairingRepository;
use WC_Logger_Interface;

class MonitorService
{
    public function __construct(
        private readonly PairingRepository   $repository,
        private readonly PairingService      $pairingService,
        private readonly WC_Logger_Interface $logger
    )
    {
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
    public function monitor(Pairing $pairing): bool
    {
        return $pairing->getIsExpress() ? $this->monitorExpress($pairing) : $this->pairingService->monitor($pairing);
    }

    public function monitorExpress(Pairing $pairing): bool
    {
        // TODO
    }
}
