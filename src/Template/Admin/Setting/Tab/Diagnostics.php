<?php

declare(strict_types=1);

namespace Twint\Woo\Template\Admin\Setting\Tab;

use Twint\Woo\Constant\TwintConstant;
use Twint\Woo\CronJob\MonitorPairingCronJob;
use Twint\Woo\Plugin;
use Twint\Woo\Service\BackgroundMonitor;
use Twint\Woo\Template\Admin\Setting\TabItem;

class Diagnostics extends TabItem
{
    public static function getKey(): string
    {
        return '_' . str_replace('\\', '_', self::class);
    }

    public static function getLabel(): string
    {
        return __('Diagnostics', 'twint-woocommerce-extension');
    }

    public static function fields(): array
    {
        return [];
    }

    public static function getContents(array $data = []): string
    {
        $info = self::getInformation();

        $trigger = Plugin::di('cli.trigger', false);
        $trigger->handle();

        ob_start();
        require Plugin::abspath() . 'src/View/Admin/diagnostics.php';

        return ob_get_clean();
    }

    public static function getInformation(): array
    {
        $info = [];


        $info[] = [
            'label' => 'Plugin version:',
            'value' => TwintConstant::PLUGIN_VERSION,
            'valid' => true,
        ];

        $info[] = [
            'label' => 'WooCommerce version:',
            'value' => defined('WC_VERSION') ? WC_VERSION : 'Unknown',
            'valid' => true,
        ];

        $info[] = [
            'label' => 'Wordpress version:',
            'value' => get_bloginfo('version'),
            'valid' => true,
        ];

        $info[] = [
            'label' => 'Plugin install source:',
            'value' => TwintConstant::INSTALL_SOURCE,
            'valid' => true,
        ];

        $info[] = [
            'label' => 'PHP version (8.1 or later):',
            'value' => PHP_VERSION,
            'valid' => version_compare(PHP_VERSION, '8.1.0', '>='),
        ];

        $opensslVersion = defined('OPENSSL_VERSION_TEXT') ? OPENSSL_VERSION_TEXT : 'unknown';
        $isOpenSsl3 = defined('OPENSSL_VERSION_NUMBER') && OPENSSL_VERSION_NUMBER >= 0x30000000;
        $info[] = [
            'label' => __('OpenSSL version:', 'twint-woocommerce-extension'),
            'value' => $opensslVersion
                . ($isOpenSsl3 ? ' — ' . __('legacy .p12 certificates are auto-converted on upload', 'twint-woocommerce-extension') : ''),
            'valid' => true,
        ];

        $soapAvailable = class_exists('SoapClient');
        $info[] = [
            'label' => __('SOAP requirement:', 'twint-woocommerce-extension'),
            'value' => $soapAvailable ? 'Yes' : 'No',
            'valid' => $soapAvailable,
        ];

        BackgroundMonitor::requestHealthCheck();
        $health = BackgroundMonitor::health();
        $info[] = [
            'label' => __('Background processing:', 'twint-woocommerce-extension'),
            'value' => __('Action Scheduler', 'twint-woocommerce-extension'),
            'valid' => BackgroundMonitor::available(),
        ];
        $info[] = [
            'label' => __('HTTP background execution test:', 'twint-woocommerce-extension'),
            'value' => $health['message'],
            'valid' => $health['valid'],
        ];
        $scheduled = (bool) wp_next_scheduled(MonitorPairingCronJob::HOOK_NAME);
        $info[] = [
            'label' => __('Recovery schedule:', 'twint-woocommerce-extension'),
            'value' => $scheduled ? __('Scheduled', 'twint-woocommerce-extension') : __('Missing', 'twint-woocommerce-extension'),
            'valid' => $scheduled,
        ];

        $validated = get_option(TwintConstant::FLAG_VALIDATED_CREDENTIAL_CONFIG) === TwintConstant::YES;
        $info[] = [
            'label' => 'Credentials is validated:',
            'value' => $validated ? 'Yes' : 'No',
            'valid' => $validated,
        ];

        $testMode = get_option(TwintConstant::TEST_MODE) === TwintConstant::YES;
        $info[] = [
            'label' => 'Credentials with test mode:',
            'value' => $testMode ? 'Yes' : 'No',
            'valid' => $testMode,
        ];

        $pluginsMerged = self::getPluginsWithActiveCheck();
        $info[] = [
            'label' => __('Plugins:', 'twint-woocommerce-extension'),
            'value' => $pluginsMerged === [] ? 'None' : implode(', ', $pluginsMerged),
            'valid' => true,
        ];

        return $info;
    }

    public static function allowSaveChanges(): bool
    {
        return false;
    }

    /**
     * Merge installed plugins into a single list and append a checkmark for enabled ones.
     *
     * Example label: "Plugin Name (vX.Y.Z) by Author (✓)" when enabled.
     *
     * @return array<int,string>
     */
    public static function getPluginsWithActiveCheck(): array
    {
        $installed = self::buildInstalledPluginsMap();

        // Collect active plugins (site level)
        $active = [];
        $activeOption = get_option('active_plugins', []);
        if (is_array($activeOption)) {
            $active = $activeOption;
        }

        // Include network-activated plugins (multisite)
        if (function_exists('is_multisite') && is_multisite()) {
            $network = get_site_option('active_sitewide_plugins', []);
            if (is_array($network)) {
                $active = array_merge($active, array_keys($network));
            }
        }

        // Turn active list into a set for quick lookup
        $activeSet = array_fill_keys($active, true);

        $result = [];
        foreach ($installed as $path => $label) {
            $result[] = isset($activeSet[$path]) ? ($label . ' (✓)') : $label;
        }

        return $result;
    }

    /**
     * Build a map of installed plugins keyed by their file path with enriched labels.
     *
     * @return array<string,string> [pluginFile => "Name (vX.Y.Z) by Author"]
     */
    private static function buildInstalledPluginsMap(): array
    {
        $installed = [];

        if (function_exists('get_plugins')) {
            $plugins = get_plugins();
            if (is_array($plugins)) {
                foreach ($plugins as $path => $data) {
                    $name = $data['Name'] ?? $path;
                    $version = $data['Version'] ?? '';
                    $author = $data['Author'] ?? '';

                    $labelParts = array_filter([
                        $name,
                        $version ? "(v{$version})" : '',
                        $author ? "by {$author}" : '',
                    ]);

                    $installed[$path] = implode(' ', $labelParts);
                }
            }
        }

        return $installed;
    }
}
