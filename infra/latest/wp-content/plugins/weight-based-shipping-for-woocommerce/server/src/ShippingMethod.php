<?php /** @noinspection PhpMissingReturnTypeInspection */

namespace Wbs;

use WbsVendors\Dgm\Arrays\Arrays;
use WbsVendors\Dgm\Shengine\Interfaces\IProcessor;
use WbsVendors\Dgm\Shengine\Processing\Processor;
use WbsVendors\Dgm\Shengine\Units;
use WbsVendors\Dgm\Shengine\Woocommerce\Converters\PackageConverter;
use WbsVendors\Dgm\Shengine\Woocommerce\Converters\RateConverter;
use WbsVendors\Dgm\WcTools\WcTools;
use WC_Shipping_Method;
use WP_Term;


class ShippingMethod extends WC_Shipping_Method
{
    /**
     * @noinspection PhpMissingParentConstructorInspection
     * @noinspection MagicMethodsValidityInspection
     */
    public function __construct($instanceId = null)
    {
        $this->plugin_id = Plugin::ID;
        $this->id = Plugin::ID;
        $this->title = $this->method_title = 'Weight Based Shipping (Legacy)';
        $this->instance_id = absint($instanceId);

        $this->supports = [
            'shipping-zones',
            'instance-settings',
            'global-instance',
        ];

        $this->init_settings();

        if (!$this->instance_id) {
            $disabled = Plugin::globalMethods() === 'only-wbsng';
            if ($disabled) {
                $this->enabled = 'no';
            }
            else {
                $this->supports[] = 'settings';
            }
        }

        $this->method_description = 'The classic UI for weight-based shipping options. It works fine and is supported. However, for new setups, the new UI is recommended.';
    }

    public function config($config = null)
    {
        $optionKey = $this->get_option_key();

        if (func_num_args()) {
            $updated = update_option($optionKey, $config);
            if ($updated) {
                WcTools::purgeShippingCache();
            }
        }
        else {
            $config = get_option($optionKey, null);
            $config['enabled'] = WcTools::yesNo2Bool($config['enabled'] ?? true);
        }

        return $config;
    }

    public function is_available($package)
    {
        // This fixes the issue with the global method not being triggered by WooCommerce for customers having no location set.
        // It also works fine for instanced shipping methods.
        return $this->is_enabled();
    }

    public function calculate_shipping($_package = [])
    {
        $settings = Settings::instance();

        $package = PackageConverter::fromWoocommerceToCore2(
            $_package,
            WC()->cart,
            $settings->preferCustomPackagePrice,
            $settings->includeNonShippableItems
        );

        $processor = new Processor();
        $rules = $this->loadRules($processor);
        $rates = $processor->process($rules, $package);

        $_rates = RateConverter::fromCoreToWoocommerce(
            $rates,
            $this->title,
            join(':', array_filter([$this->id, @$this->instance_id])).':'
        );

        foreach ($_rates as $_rate) {
            $this->add_rate($_rate);
        }
    }

    public function admin_options()
    {
        WpTools::addActionOrCall('admin_enqueue_scripts', [$this, '_enqueueAssets'], PHP_INT_MAX);
        parent::admin_options();
    }

    public function get_admin_options_html()
    {
        ob_start();

        if ($this->showGlobalSettingsStub()) {
            include(Plugin::instance()->meta->paths->globalStubTplFile);
        }
        else {
            include(Plugin::instance()->meta->paths->tplFile);
        }

        return ob_get_clean();
    }

    public function get_instance_id()
    {
        // A hack to prevent Woocommerce 2.6 from skipping global method instance
        // rates in WC_Shipping::calculate_shipping_for_package()
        return (method_exists('parent', 'get_instance_id') ? parent::get_instance_id() : $this->instance_id) ?: -1;
    }

    public function get_option_key()
    {
        return join('_', array_filter([
            $this->plugin_id,
            $this->instance_id,
            'config',
        ]));
    }

    public function init_settings()
    {
        $this->settings = $this->config();
        $this->enabled = $this->settings['enabled'] = WcTools::bool2YesNo($this->settings['enabled']);
    }

    public function get_instance_option_key()
    {
        return $this->get_option_key();
    }

    public function init_instance_settings()
    {
        $this->init_settings();
    }

    public function _enqueueAssets()
    {
        $plugin = Plugin::instance();
        $version = $plugin->meta->version;
        $paths = $plugin->meta->paths;

        // Firefox and Safari, with Yoast SEO active, throws "TypeError: can't convert undefined to object" error.
        // We'd better handle that for all browsers just to get a consistent result.
        if (defined('WPSEO_VERSION')) {
            WpTools::addActionOrCall('wp_print_scripts', static function() use ($paths) {
                WpTools::removeScripts(["#(/|\\\\)wp-seo-babel#"], $paths->getAssetUrl());
            });
        }

        add_action('admin_footer', static function() {
            wp_dequeue_script('wc-ppcp-admin-notice');
        }, PHP_INT_MAX);

        if (defined('WBS_DEV')) {
            wp_register_script('wbs-polyfills', $paths->getAssetUrl('polyfills.js'));
            wp_register_script('wbs-vendor', $paths->getAssetUrl('vendor.js'), ['wbs-polyfills']);
            wp_enqueue_script('wbs-app', $paths->getAssetUrl('app.js'), ['jquery', 'wbs-polyfills', 'wbs-vendor']);
        }
        else {
            wp_enqueue_script('wbs-app', $paths->getAssetUrl('client.js'), ['jquery'], $version);
        }

        wp_enqueue_script('jquery-ui-sortable');

        $currencyPlacement = explode('_', get_option('woocommerce_currency_pos'));

        /** @noinspection PhpUndefinedClassInspection */
        /** @noinspection PhpUndefinedNamespaceInspection */
        wp_localize_script('wbs-app', 'wbs_js_data', [

            'locations' => self::getAllLocations(),

            'shippingClasses' => self::getAllShippingClasses(),

            'weightUnit' => get_option('woocommerce_weight_unit'),

            'currency' => [
                'symbol' => html_entity_decode(get_woocommerce_currency_symbol()),
                'right' => $currencyPlacement[0] === 'right',
                'withSpace' => @$currencyPlacement[1] === 'space',
            ],

            'config' => $this->config(),

            'isGlobalInstance' => empty($this->instance_id),

            'endpoints' => [
                'config' => Api::$config->url(['instance_id' => $this->instance_id]),
            ],

            'wcpre441' => !Plugin::wc441plus(),

            'globalMethods' => ($this->instance_id || !class_exists(\Gzp\WbsNg\Plugin::class)) ? null : [
                'state' => Plugin::globalMethods(),
                'endpoint' => Api::$globalSwitch->url(),
                'wbsngRedirectUrl' => Plugin::shippingUrl(\Gzp\WbsNg\Plugin::ID),
            ],
        ]);
    }

    private static function getStateCode($cc, $sc = null)
    {
        if (self::isWildcardStateCode($sc)) {
            $sc = null;
        }

        return rtrim("{$cc}:{$sc}", ":");
    }

    private static function isWildcardStateCode($sc)
    {
        return !$sc || $sc === '*';
    }

    private static function getAllLocations()
    {
        $locations = [];

        foreach (WC()->countries->get_shipping_countries() as $cc => $country) {

            $country = html_entity_decode($country);

            $locations[] = [
                'id' => self::getStateCode($cc, '*'),
                'name' => $country,
            ];

            if ($states = WC()->countries->get_states($cc)) {

                foreach ($states as $sc => $state) {

                    $state = html_entity_decode($state);

                    $locations[] = [
                        'id' => self::getStateCode($cc, $sc),
                        'name' => "{$country} — {$state}",
                    ];
                }
            }
        }

        return $locations;
    }

    private static function getAllShippingClasses()
    {
        return Arrays::map(WC()->shipping()->get_shipping_classes(), static function(WP_Term $term) {
            /** @noinspection PhpCastIsUnnecessaryInspection  $term field types aren't guaranteed */
            return [
                'id' => (string)$term->term_id,
                'name' => (string)$term->name,
                'slug' => (string)$term->slug,
            ];
        });
    }

    private function loadRules(IProcessor $processor)
    {
        $_rules = $this->config()['rules'] ?? [];

        $mapper = new RulesMapper(
            Units::fromPrecisions(
                10 ** wc_get_price_decimals(),
                1000,
                1000
            ),
            $processor
        );

        $rules = $mapper->read($_rules);

        return $rules;
    }

    private function showGlobalSettingsStub(): bool
    {
        if ($this->instance_id || isset($_GET['wbs_global'])) {
            return false;
        }

        return empty($this->config()['rules']);
    }
}