<?php

declare(strict_types=1);

namespace Twint\Woo\Setup;

use Twint\Woo\Constant\TwintConstant;
use Twint\Woo\Container\Lazy;
use Twint\Woo\Container\LazyLoadTrait;
use Twint\Woo\CronJob\MonitorPairingCronJob;

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
    }

    private function upgradeSchema(): void
    {
        foreach ($this->migrations as $migration) {
            $migration->up();
        }
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
