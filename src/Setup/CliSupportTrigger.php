<?php

declare(strict_types=1);

namespace Twint\Woo\Setup;

use Symfony\Component\Process\Process;
use Throwable;
use Twint\Woo\Command\CliCommand;
use Twint\Woo\Plugin;
use WC_Logger_Interface;

class CliSupportTrigger
{
    public function __construct(
        private WC_Logger_Interface $logger
    ) {
    }

    public function handle(): void
    {
        try {
            $process = new Process(['php', Plugin::abspath() . 'bin/console', CliCommand::COMMAND]);
            $process->setOptions([
                'create_new_console' => true,
            ]);
            $process->disableOutput();
            $process->start();
        } catch (Throwable $e) {
            $this->logger->error('Cannot start PHP process: ' . $e->getMessage());
        }
    }
}
