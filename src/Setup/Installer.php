<?php

declare(strict_types=1);

namespace Twint\Woo\Setup;

use Twint\Woo\Constant\TwintConstant;
use Twint\Woo\Container\Lazy;
use Twint\Woo\Container\LazyLoadTrait;
use Twint\Woo\CronJob\MonitorPairingCronJob;
use Twint\Woo\Repository\PairingRepository;

/**
 * @method CliSupportTrigger getTrigger()
 */
class Installer
{
    use LazyLoadTrait;

    protected static array $lazyLoads = ['trigger'];

    public function __construct(
        private readonly array         $migrations,
        private Lazy|CliSupportTrigger $trigger
    ) {
    }

    public function install(): void
    {
        $this->upgradeSchema();

        $this->setDefaultConfigs();

        $this->getTrigger()->handle();

        MonitorPairingCronJob::scheduleCronjob();

        $this->finalizeDbVersion();
    }

    public function upgrade(): void
    {
        $this->upgradeSchema();
    }

    public function columnsReady(): bool
    {
        global $wpdb;

        $cols = $wpdb->get_col($wpdb->prepare(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s
               AND COLUMN_NAME IN ('lock_version', 'order_settled')",
            PairingRepository::tableName()
        ));

        return count($cols) === 2;
    }

    private function upgradeSchema(): void
    {
        foreach ($this->migrations as $migration) {
            $migration->up();
        }
    }

    private function finalizeDbVersion(): void
    {
        if ($this->columnsReady()) {
            update_option('twint_db_version', TwintConstant::TWINT_DB_VERSION);
            delete_option('twint_migration_failed');
            delete_transient('twint_migration_retry_after');
        }

        global $wpdb;
        $now = $wpdb->get_var('SELECT NOW()');
        add_option('twint_upgraded_at', $now);
    }

    private function setDefaultConfigs(): void
    {
        // Init setting for payment gateway, avoid wiping from updating.
        $existing = get_option('woocommerce_twint_regular_settings', []);
        if (!is_array($existing)) {
            $existing = [];
        }

        $defaults = [
            'enabled' => 'yes',
            'title' => 'TWINT',
        ];

        update_option('woocommerce_twint_regular_settings', array_merge($defaults, $existing));

        if (get_option('twint_express_checkout_display_options') === false) {
            update_option('twint_express_checkout_display_options', TwintConstant::DEFAULT_DISPLAYS);
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
