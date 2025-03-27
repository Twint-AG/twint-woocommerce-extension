<?php

declare(strict_types=1);

namespace Twint\Woo\Setup;

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
            $logFile = escapeshellarg(sys_get_temp_dir() . '/cli_command.log');
            $subCommand = escapeshellarg(Plugin::abspath() . 'bin/console');
            $name = escapeshellarg(CliCommand::COMMAND);
            $command = "php {$subCommand} {$name} > {$logFile} 2>&1 &";

            if (function_exists('shell_exec')) {
                shell_exec($command);
            }
        } catch (Throwable $e) {
            $this->logger->error('Cannot start PHP process: ' . $e->getMessage());
        }
    }
}
