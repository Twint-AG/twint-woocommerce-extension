<?php

namespace TWINT\Woo\Services;

use Exception;
use Throwable;
use Twint\Sdk\Exception\SdkError;
use Twint\Woo\App\Model\Pairing;

class MonitorService
{
    private PairingService $pairingService;
    private PairingService $regular;

    public function __construct()
    {
        $this->pairingService = new PairingService();
        $this->regular = new PairingService();
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function monitors(): void
    {
        $pairings = $this->pairingService->loadInProcessPairings();

        /** @var Pairing $pairing */
        foreach ($pairings as $pairing) {
            try {
                $this->monitor($pairing);
            } catch (Throwable $e) {
                // Silent error to allow process handle next Pairings
                wc_get_logger()->error("TWINT cli error: {$pairing->getId()} {$pairing->getToken()} {$e->getMessage()}");
            }
        }
    }

    /**
     * @throws Throwable
     */
    public function monitor(Pairing $pairing): mixed
    {
        return $pairing->getIsExpress() ? $this->monitorExpress($pairing) : $this->regular->monitor($pairing);
    }

    public function monitorExpress(Pairing $pairing)
    {
        // TODO
    }
}