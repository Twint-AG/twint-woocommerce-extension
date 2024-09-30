<?php

declare(strict_types=1);

namespace Twint\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Twint\Woo\Constant\TwintConstant;
use Twint\Woo\Repository\PairingRepository;
use Twint\Woo\Service\MonitorService;

/**
 * @method PairingRepository getRepository()
 * @method MonitorService getMonitor()
 */
#[AsCommand(name: 'twint:cli')]
class CliCommand extends Command
{
    public const COMMAND = 'twint:cli';

    protected function configure(): void
    {
        $this->setName(self::COMMAND);
        $this->setDescription('Detect if the system has CLI support.');
    }

    /**
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        update_option(TwintConstant::CONFIG_CLI_SUPPORT_OPTION, 'Yes');

        return 0;
    }
}
