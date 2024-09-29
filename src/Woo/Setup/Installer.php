<?php

declare(strict_types=1);

namespace Twint\Woo\Setup;

use Twint\Plugin;
use Twint\Woo\CronJob\MonitorPairingCronJob;

class Installer
{
    public function __construct(
        private readonly array $migrations
    )
    {
    }

    public function install(): void
    {
        $this->upgradeSchema();

        $this->setDefaultConfigs();

        MonitorPairingCronJob::scheduleCronjob();

        $this->copyLanguagePacks();
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
    }

    private function copyLanguagePacks(): void
    {
        $pluginLanguagesPath = Plugin::abspath() . 'i18n/languages/';
        $wpLangPluginPath = WP_CONTENT_DIR . '/languages/plugins/';

        if (!$this->folderExist($wpLangPluginPath)) {
            mkdir($wpLangPluginPath, 0777, true);
        }
        $pluginLanguagesDirectory = array_diff(scandir($pluginLanguagesPath), ['..', '.']);
        foreach ($pluginLanguagesDirectory as $language) {
            @copy($pluginLanguagesPath . $language, $wpLangPluginPath . $language);
        }
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
