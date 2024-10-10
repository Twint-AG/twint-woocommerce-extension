<?php

declare(strict_types=1);

namespace Twint\Woo\Setup;

use Twint\Plugin;
use Twint\Woo\Constant\TwintConstant;
use Twint\Woo\CronJob\MonitorPairingCronJob;

class Installer
{
    public function __construct(
        private readonly array $migrations,
        private readonly CliSupportTrigger $trigger
    ) {
    }

    public function install(): void
    {
        $this->upgradeSchema();

        $this->setDefaultConfigs();

        $this->trigger->handle();

        MonitorPairingCronJob::scheduleCronjob();
    }

    private function upgradeSchema(): void
    {
        foreach ($this->migrations as $migration) {
            $migration->up();
        }
    }

    private function setDefaultConfigs(): void
    {
        // Init setting for payment gateway
        $initData = [
            'enabled' => 'yes',
            'title' => 'TWINT',
        ];
        update_option('woocommerce_twint_regular_settings', $initData);
        update_option(TwintConstant::CONFIG_CLI_SUPPORT_OPTION, 'No');
    }

    public function folderExist($folder): bool|string
    {
        $path = realpath($folder);
        if ($path !== false && is_dir($path)) {
            return $path;
        }

        return false;
    }
}
