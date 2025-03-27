<?php

namespace Wbs;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Wbs\Migrations\ConfigStorage;
use WbsVendors\Dgm\PluginServices\ServiceInstaller;
use WbsVendors\Dgm\Shengine\Migrations\MigrationService;
use WbsVendors\Dgm\Shengine\Migrations\Storage\WordpressOptions;
use WC_Cache_Helper;


/**
 * @property-read PluginMeta $meta
 */
class Plugin
{
    const ID = 'wbs';


    public static function setupOnce(string $entrypoint): void
    {
        if (!isset(self::$instance)) {
            $plugin = new Plugin($entrypoint);
            $plugin->setup();
            self::$instance = $plugin;
        }
    }

    public static function instance(): self
    {
        return self::$instance;
    }

    public static function shippingUrl($section = null): ?string
    {
        $query = [
            "page" => "wc-settings",
            "tab" => "shipping",
        ];

        if (isset($section)) {
            $query['section'] = $section;
        }

        $query = http_build_query($query, '', '&');

        return admin_url("admin.php?{$query}");
    }

    public static function wc441plus(): bool
    {
        /** @noinspection PhpUndefinedConstantInspection */
        return !defined('WC_VERSION') || version_compare(WC_VERSION, '4.4.1', '>=');
    }

    public static function globalMethods(): string
    {
        $v = get_option('wbs_global_methods') ?: 'only-wbsng';

        /** @noinspection PhpUndefinedClassInspection */
        /** @noinspection PhpUndefinedNamespaceInspection */
        if (($v === 'only-wbsng' || $v === 'both') && !class_exists(\Gzp\WbsNg\Plugin::class)) {
            $v = 'only-wbs';
        }

        return $v;
    }

    public function __construct(string $entrypoint)
    {
        $entrypoint = wp_normalize_path($entrypoint);

        $this->entrypoint = $entrypoint;
        $root = dirname($this->entrypoint).'/server';
        $this->meta = new PluginMeta($entrypoint, $root);
    }

    public function setup()
    {
        register_activation_hook($this->entrypoint, [$this, '__resetShippingCache']);
        register_deactivation_hook($this->entrypoint, [$this, '__resetShippingCache']);

        add_filter('woocommerce_shipping_methods', [$this, '__woocommerceShippingMethods']);
        add_filter('plugin_action_links_'.plugin_basename($this->entrypoint), [$this, '__pluginActionLinks']);

        ServiceInstaller::create()->installIfReady($this->createMigrationService());

        Api::init();

        add_action('before_woocommerce_init', function() {
            if (class_exists(FeaturesUtil::class)) {
                FeaturesUtil::declare_compatibility('custom_order_tables', $this->entrypoint, true);
            }
        });

        $this->fixIncorrectHidingOfShippingSectionWhenNoShippingZoneMethodsDefined();
    }


    /**
     * @internal
     */
    function __woocommerceShippingMethods(/** @noinspection PhpDocSignatureInspection */ $shippingMethods)
    {
        $shippingMethods[self::ID] = ShippingMethod::class;
        return $shippingMethods;
    }

    /**
     * @internal
     */
    function __pluginActionLinks(/** @noinspection PhpDocSignatureInspection */ $links)
    {
        $newLinks = [];
        $newLinks[self::shippingUrl()] = 'Shipping zones';
        $newLinks[self::shippingUrl(self::globalMethods() === 'only-wbs' ? self::ID : 'wbsng')] = 'Global shipping rules';

        foreach ($newLinks as $url => &$text) {
            $text = '<a href="'.esc_html($url).'">'.esc_html($text).'</a>';
        }
        unset($text);

        array_splice($links, 0, 0, $newLinks);

        return $links;
    }

    /**
     * @internal
     */
    function __resetShippingCache()
    {
        $reset = function() {
            WC_Cache_Helper::get_transient_version('shipping', true);
        };

        if (did_action('woocommerce_init')) {
            $reset();
        }
        else {
            add_action('woocommerce_init', $reset);
        }
    }

    /**
     * @param string $property
     * @return PluginMeta|null
     * @internal
     */
    function __get($property)
    {
        if ($property === 'meta') {
            return $this->meta;
        }

        trigger_error("Undefined property '{$property}'", E_USER_NOTICE);
        return null;
    }


    /** @var self */
    private static $instance;

    /** @var string */
    private $entrypoint;

    /** @var PluginMeta */
    private $meta;


    private function createMigrationService(): MigrationService
    {
        global $wpdb;

        $options = new WordpressOptions($wpdb);

        return new MigrationService(
            $this->meta->version,
            $options->bind('wbs_config_version'),
            $this->meta->paths->root.'/migrations',
            new ConfigStorage('wbs\\_%config', $options)
        );
    }

    private function fixIncorrectHidingOfShippingSectionWhenNoShippingZoneMethodsDefined(): void
    {
        add_action('woocommerce_init', function() {

            /** @noinspection PhpUnusedLocalVariableInspection */
            $transient = $field = null;
            $wcver = WC()->version;
            if (version_compare($wcver, '9.7.0') >= 0) {
                $transient = 'transient_wc_shipping_method_count';
                $field = 'legacy';
            }
            else if (version_compare($wcver, '3.6.0') >= 0) {
                $transient = 'transient_wc_shipping_method_count_legacy';
                $field = 'value';
            }
            else {
                return;
            }

            add_filter($transient, function($value) use ($field) {
                static $running = false;

                if ($running) return $value;
                $running = true;
                try {
                    $trv = WC_Cache_Helper::get_transient_version('shipping');
                    if ($value === null || (int)($value[$field] ?? null) === 0 || ($value['version'] ?? null) !== $trv) {
                        $value[$field] = max(1, wc_get_shipping_method_count(true));
                        $value['version'] = $trv;
                    }
                }
                finally {
                    $running = false;
                }

                return $value;
            }, PHP_INT_MAX);
        }, 0);
    }
}