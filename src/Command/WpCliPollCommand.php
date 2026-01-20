<?php

declare(strict_types=1);

namespace Twint\Woo\Command;

use DateTime;
use Twint\Woo\Model\Pairing;
use Twint\Woo\Plugin;
use Twint\Woo\Repository\PairingRepository;
use Twint\Woo\Service\MonitorService;
use WC_Logger_Interface;
use WP_CLI;

class WpCliPollCommand
{
    private PairingRepository $repository;

    private MonitorService $monitor;

    private WC_Logger_Interface $logger;

    public function __construct()
    {
        $this->repository = Plugin::di('pairing.repository', false);
        $this->monitor = Plugin::di('monitor.service', false);
        $this->logger = Plugin::di('logger', false);
    }

    /**
     * Monitor a TWINT pairing until finished.
     *
     * ## OPTIONS
     *
     * <pairing-id>
     * : The ID of the TWINT pairing to monitor.
     *
     * ## EXAMPLES
     *
     * wp twint-poll 123
     *
     * @when after_wp_load
     * @param mixed $args
     * @param mixed $assoc_args
     */
    public function poll($args, $assoc_args): void
    {
        [$id] = $args;

        $pairing = $this->repository->get($id);
        if (!$pairing instanceof Pairing) {
            WP_CLI::error("Pairing with ID {$id} not found.");
        }

        $count = 1;
        $startedAt = new DateTime();

        WP_CLI::log("Monitoring: {$id}");
        $this->logger->info("TWINT WpCliPollCommand::poll: monitoring {$id}");

        while (!$pairing->isFinished()) {
            $this->repository->updateCheckedAt($pairing);
            $this->monitor->monitor($pairing);

            sleep($this->getInterval($pairing, $startedAt));
            $pairing = $this->repository->get($id);
            $count++;
        }

        WP_CLI::success("Pairing {$id} is finished after {$count} checks.");
    }

    private function getInterval(Pairing $pairing, DateTime $startedAt): int
    {
        $now = new DateTime();
        $elapsed = $now->getTimestamp() - $startedAt->getTimestamp();

        if ($pairing->getIsExpress()) {
            return $elapsed < 10 * 60 ? 2 : 10;
        }

        return $elapsed < 3 * 60 ? 5 : 10;
    }
}
