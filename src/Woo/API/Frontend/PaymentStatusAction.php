<?php

declare(strict_types=1);

namespace Twint\Woo\Api\Frontend;

use Symfony\Component\Process\Process;
use Twint\Command\PollCommand;
use Twint\Plugin;
use Twint\Woo\Api\BaseAction;
use Twint\Woo\Model\Pairing;
use Twint\Woo\Repository\PairingRepository;
use WC_Logger_Interface;
use WP_REST_Request;
use WP_REST_Response;

class PaymentStatusAction extends BaseAction
{
    private const TIME_WINDOW_SECONDS = 10; // 10 seconds

    public function __construct(
        private readonly PairingRepository $pairingRepository,
        private readonly WC_Logger_Interface $logger
    )
    {
        $this->registerHooks();
    }

    protected function registerHooks(): void
    {
        add_action('rest_api_init', function () {
            register_rest_route('twint/v1', '/payment/status', [
                'methods' => 'POST',
                'callback' => [$this, 'handle'],
                'permission_callback' => '__return_true'
            ]);
        });
    }

    public function handle(WP_REST_Request $request): WP_REST_Response
    {
        $pairingId = $request->get_param('pairingId');

        $pairing = $this->pairingRepository->findById($pairingId);
        if (!$pairing instanceof Pairing) {
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

        return new WP_REST_Response([
            'success' => true,
            'isOrderPaid' => $pairing->isFinished(),
            'status' => $pairing->getStatus(),
        ], 200);
    }

    protected function isRunning(Pairing $pairing): bool
    {
        return $pairing->getCheckedAt() && $pairing->getCheckedAgo() < self::TIME_WINDOW_SECONDS;
    }
}
