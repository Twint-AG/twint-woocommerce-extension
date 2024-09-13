<?php

declare(strict_types=1);

namespace Twint\Woo\Api\Frontend;

use Symfony\Component\Process\Process;
use Throwable;
use Twint\Command\PollCommand;
use Twint\Plugin;
use Twint\Woo\Api\BaseAction;
use Twint\Woo\Model\Pairing;
use Twint\Woo\Repository\PairingRepository;
use WC_Logger_Interface;

class PairingMonitoringAction extends BaseAction
{
    private const TIME_WINDOW_SECONDS = 10; // 10 seconds

    public function __construct(
        private readonly PairingRepository $pairingRepository,
        private readonly WC_Logger_Interface $logger
    ) {
        add_action('wp_ajax_nopriv_twint_check_pairing_status', [$this, 'requireLogin']);
        add_action('wp_ajax_twint_check_pairing_status', [$this, 'monitorPairing']);
    }

    /**
     * @throws Throwable
     */
    public function monitorPairing(): void
    {
        if (!wp_verify_nonce($_REQUEST['nonce'], 'twint_check_pairing_status')) {
            exit('The WP Nonce is invalid, please check again!');
        }

        $pairingId = $_REQUEST['pairingId'];
        $pairing = $this->pairingRepository->findById($pairingId);
        if (!$pairing) {
            exit('The pairing for the the order does not exist.');
        }

        if (!$pairing->isFinished() && !$this->isRunning($pairing)) {
            $this->logger->info("[TWINT] - Checking pairing [{$pairingId}]...");

            $process = new Process(['php', Plugin::abspath() . 'bin/console', PollCommand::COMMAND, $pairingId]);
            $process->setOptions([
                'create_new_console' => true,
            ]);
            $process->disableOutput();
            $process->start();
        }

        echo json_encode([
            'success' => true,
            'isOrderPaid' => $pairing->isFinished(),
            'status' => $pairing->getStatus(),
        ]);

        die();
    }

    protected function isRunning(Pairing $pairing): bool
    {
        return $pairing->getCheckedAt() && $pairing->getCheckedAgo() < self::TIME_WINDOW_SECONDS;
    }
}
