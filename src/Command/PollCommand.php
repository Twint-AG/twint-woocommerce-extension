<?php

declare(strict_types=1);

namespace Twint\Command;

use DateTime;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Twint\Plugin;
use Twint\Woo\Model\Pairing;
use Twint\Woo\Repository\PairingRepository;
use Twint\Woo\Service\MonitorService;
use WC_Logger_Interface;

#[AsCommand(name: 'twint:poll')]
class PollCommand extends Command
{
    public const COMMAND = 'twint:poll';

    private PairingRepository $repository;

    private MonitorService $monitor;

    private WC_Logger_Interface $logger;

    public function __construct()
    {
        parent::__construct();

        $this->logger = Plugin::di('logger');
        $this->repository = Plugin::di('pairing.repository');
        $this->monitor = Plugin::di('monitor.service');
    }

    protected function configure(): void
    {
        $this->setName(self::COMMAND);
        $this->addArgument('pairing-id', InputArgument::REQUIRED, 'ID (primary key) of existing TWINT pairings');
        $this->setDescription('Monitoring Pairing');
    }

    /**
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getArgument('pairing-id');
        $pairing = $this->repository->get($id);

        $count = 1;
        $startedAt = new DateTime();

        while (!$pairing->isFinished()) {
            $output->writeln("<info>Checking count: {$count}</info>");
            $this->logger->info(
                "[TWINT] - monitoring: {$id}: {$pairing->getVersion()} {$pairing->getCreatedAgo()}"
            );
            $this->repository->updateCheckedAt($pairing);

            $this->monitor->monitor($pairing);

            sleep($this->getInterval($pairing, $startedAt));
            $pairing = $this->repository->get($id);
            ++$count;
        }

        return 0;
    }

    /**
     * Regular: first 3m every 5s, afterwards 10s
     * Express: first 10m every 2s, afterwards 10s
     */
    private function getInterval(Pairing $pairing, DateTime $startedAt): int
    {
        $now = new DateTime();
        $interval = $now->diff($startedAt);
        $seconds = $interval->s + ($interval->i * 60) + ($interval->h * 3600) + ($interval->d * 86400);

        if ($pairing->getIsExpress()) {
            return $seconds < 10 * 60 ? 2 : 10;
        }

        return $seconds < 5 * 60 ? 2 : 10;
    }
}
