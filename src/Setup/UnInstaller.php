<?php

declare(strict_types=1);

namespace Twint\Woo\Setup;

use Twint\Woo\Constant\TwintConstant;
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

        /**
         * Clean up credentials and according configurations.
         */
        $configSettingKeys = [
            TwintConstant::STORE_UUID,
            TwintConstant::KEY_PRIMARY_SETTING,
            TwintConstant::FLAG_VALIDATED_CREDENTIAL_CONFIG,
            TwintConstant::TEST_MODE,
            TwintConstant::CONFIG_EXPRESS_SCREENS,
            TwintConstant::CONFIG_EXPRESS_ORG_SCREENS,
            TwintConstant::CONFIG_CLI_SUPPORT_OPTION,
            TwintConstant::CERTIFICATE,
        ];

        foreach ($configSettingKeys as $configSettingKey) {
            delete_option($configSettingKey);
        }

        MonitorPairingCronJob::removeCronjob();
    }
}
