<?php

declare(strict_types=1);

namespace Twint\Woo\Command;

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

    public function __construct(?string $name = null)
    {
        parent::__construct($name);
    }

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
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            echo 'This command requires PHP 8.1 or higher. Current version: ' . PHP_VERSION;
            return 1;
        }

        update_option(TwintConstant::CONFIG_CLI_SUPPORT_OPTION, 'Yes');

        echo 'The TWINT command was successfully executed via the PHP CLI.';

        return 0;
    }
}
