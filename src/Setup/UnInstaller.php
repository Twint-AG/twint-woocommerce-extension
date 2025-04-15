<?php

declare(strict_types=1);

namespace Twint\Woo\Setup;

use Twint\Woo\CronJob\MonitorPairingCronJob;
use Twint\Woo\Service\SettingService;

class UnInstaller
{
    public function __construct(
        private readonly array $migrations
    ) {
    }

    public function uninstall(): void
    {
        /**
         * Do we need to remove the table when deactivating plugin?
         */
        if (SettingService::getAutoRemoveDBTableWhenDisabling() === 'yes') {
            foreach (array_reverse($this->migrations) as $migration) {
                $migration->down();
            }
        }

        MonitorPairingCronJob::removeCronjob();
    }
}
