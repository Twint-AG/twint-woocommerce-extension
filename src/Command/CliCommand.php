<?php

declare(strict_types=1);

namespace Twint\Woo\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Twint\Woo\Constant\TwintConstant;
use Twint\Woo\Plugin;
use Twint\Woo\Repository\PairingRepository;
use Twint\Woo\Service\MonitorService;
use WC_Logger_Interface;

/**
 * @method PairingRepository getRepository()
 * @method MonitorService getMonitor()
 */
#[AsCommand(name: 'twint:cli')]
class CliCommand extends Command
{
    public const COMMAND = 'twint:cli';

    private WC_Logger_Interface $logger;

    public function __construct(?string $name = null)
    {
        parent::__construct($name);

        $this->logger = Plugin::di('logger', true);
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
        $this->logger->info('CliCommand::execute is running');

        update_option(TwintConstant::CONFIG_CLI_SUPPORT_OPTION, 'Yes');

        return 0;
    }
}
