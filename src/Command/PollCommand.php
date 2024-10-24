<?php

declare(strict_types=1);

namespace Twint\Woo\Command;

use DateTime;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Twint\Woo\Container\Lazy;
use Twint\Woo\Container\LazyLoadTrait;
use Twint\Woo\Model\Pairing;
use Twint\Woo\Plugin;
use Twint\Woo\Repository\PairingRepository;
use Twint\Woo\Service\MonitorService;
use WC_Logger_Interface;

/**
 * @method PairingRepository getRepository()
 * @method MonitorService getMonitor()
 */
#[AsCommand(name: 'twint:poll')]
class PollCommand extends Command
{
    use LazyLoadTrait;

    public const COMMAND = 'twint:poll';

    protected static array $lazyLoads = ['repository', 'monitor'];

    private Lazy|PairingRepository $repository;

    private Lazy|MonitorService $monitor;

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
        $pairing = $this->getRepository()
            ->get($id);

        $count = 1;
        $startedAt = new DateTime();

        $output->writeln("Monitoring: <info>{$id}</info>");
        $this->logger->info("[TWINT] - Monitoring: {$id}");

        while (!$pairing->isFinished()) {
            $this->getRepository()
                ->updateCheckedAt($pairing);

            $this->getMonitor()
                ->monitor($pairing);

            sleep($this->getInterval($pairing, $startedAt));
            $pairing = $this->getRepository()
                ->get($id);
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
