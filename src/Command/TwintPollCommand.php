<?php

namespace Twint\Command;

use DateTime;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Twint\Woo\App\Model\Pairing;
use Twint\Woo\Services\MonitorService;
use Twint\Woo\Services\PairingService;

#[AsCommand(name: 'twint:poll')]
class TwintPollCommand extends Command
{
    const COMMAND = 'twint:poll';
    private PairingService $pairingService;
    private MonitorService $monitorService;

    public function __construct()
    {
        $this->pairingService = new PairingService();
        $this->monitorService = new MonitorService();
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('twint:poll');
        $this->addArgument('pairing-id', InputArgument::REQUIRED, 'ID (primary key) of existing TWINT pairings');
        $this->setDescription('Monitoring Pairing');
    }

    /**
     * @throws \Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pairingId = $input->getArgument('pairing-id');
        $pairing = $this->pairingService->findById($pairingId);

        $count = 1;
        $startedAt = new DateTime();

        while (!$pairing->isFinished()) {
            $output->writeln("<info>Checking count: {$count}</info>");
            wc_get_logger()->info("[TWINT] - monitoring: {$pairingId}: {$pairing->getVersion()}");
            $this->pairingService->updateCheckedAt($pairing);

            $this->monitorService->monitor($pairing);
            $pairing = $this->pairingService->findById($pairingId);

            sleep($this->getInterval($pairing, $startedAt));
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