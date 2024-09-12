<?php

namespace Twint;

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Automattic\WooCommerce\Utilities\OrderUtil;
use Twint\Woo\Gateway\ExpressCheckoutGateway;
use Twint\Woo\Gateway\RegularCheckoutGateway;
use Twint\Woo\Model\Method\ExpressCheckout;
use Twint\Woo\Model\Method\RegularCheckout;
use Twint\Woo\TwintIntegration;

class TwintPayment
{
    /**
     * Plugin bootstrapping.
     */
    public static function init(): void
    {
        // Twint Payments gateway class.
        add_action('plugins_loaded', [__CLASS__, 'includes'], 0);

        // Make the Twint Payment gateway available to WC.
        add_filter('woocommerce_payment_gateways', [__CLASS__, 'addGateway']);

        // Registers WooCommerce Blocks integration.
        add_action('woocommerce_blocks_loaded', [__CLASS__, 'wooGatewayTwintBlockSupport']);

        $instance = new TwintIntegration();
        add_filter('plugin_action_links_' . plugin_basename((new TwintPayment)->pluginFile()), array($instance, 'adminPluginSettingsLink'));

        register_activation_hook((new TwintPayment)->pluginFile(), [TwintIntegration::class, 'install']);
        register_deactivation_hook((new TwintPayment)->pluginFile(), [TwintIntegration::class, 'uninstall']);

        add_action('init', [__CLASS__, 'createCustomWooCommerceStatus']);
        add_filter('wc_order_statuses', [__CLASS__, 'addCustomWooCommerceStatusToList']);

        // Declare compatibility with WooCommerce HPOS
        add_action('before_woocommerce_init',static function () {
            if (class_exists(OrderUtil::class)) {
                FeaturesUtil::declare_compatibility('custom_order_tables', (new TwintPayment)->pluginFile());
            }
        });
    }

    protected function pluginFile(): string
    {
        return __DIR__.'/../woocommerce-gateway-twint.php';
    }

    public static function createCustomWooCommerceStatus(): void
    {
        register_post_status(
            RegularCheckoutGateway::getOrderStatusAfterPartiallyRefunded(),
            [
                'label' => __('Refunded (partially)', 'woocommerce-gateway-twint'),
                'public' => true,
                'show_in_admin_all_list' => true,
                'show_in_admin_status_list' => true,
            ]
        );
    }

    public static function addCustomWooCommerceStatusToList($orderStatuses): array
    {
        $orderStatuses[RegularCheckoutGateway::getOrderStatusAfterPartiallyRefunded()] = __('Refunded (partially)', 'woocommerce-gateway-twint');
        return $orderStatuses;
    }

    /**
     * Add the Twint Payment gateway to the list of available gateways.
     *
     * @param array $gateways
     * @return array
     */
    public static function addGateway(array $gateways): array
    {
        $currency = get_woocommerce_currency();
        if ($currency !== 'CHF') {
            return $gateways;
        }

        /**
         * Insert Twint Regular Checkout payment method into the woo payment methods
         */
        $gateways[] = RegularCheckoutGateway::class;

        /**
         * Insert Twint Express Checkout payment method into the woo payment methods
         */
        $gateways[] = ExpressCheckoutGateway::class;

        return $gateways;
    }

    /**
     * Is plugin active.
     *
     * @param string $plugin Plugin Name.
     * @return  bool
     * @version 1.0.0
     * @since  1.0.0
     */
    public static function isPluginActivated(string $plugin): bool
    {
        return (function_exists('is_plugin_active') ? is_plugin_active($plugin) :
            (
                in_array($plugin, apply_filters('active_plugins', (array)get_option('active_plugins', array())), true) ||
                (is_multisite() && array_key_exists($plugin, (array)get_site_option('active_sitewide_plugins', array())))
            )
        );
    }

    /**
     * Plugin includes.
     */
    public static function includes(): void
    {
        // Check for active plugins.
        if (!self::isPluginActivated('woocommerce/woocommerce.php')) {
            return;
        }
    }

    /**
     * Plugin url.
     *
     * @return string
     */
    public static function plugin_url(): string
    {
        return untrailingslashit(plugins_url('/', (new TwintPayment)->pluginFile()));
    }

    /**
     * Plugin url.
     *
     * @return string
     */
    public static function abspath(): string
    {
        return trailingslashit(plugin_dir_path((new TwintPayment)->pluginFile()));
    }

    /**
     * Registers WooCommerce Blocks integration.
     *
     */
    public static function wooGatewayTwintBlockSupport(): void
    {
        if (class_exists(PaymentMethodRegistry::class)) {
            add_action(
                'woocommerce_blocks_payment_method_type_registration',
                function (PaymentMethodRegistry $payment_method_registry) {
                    $payment_method_registry->register(new ExpressCheckout());
                    $payment_method_registry->register(new RegularCheckout());
                }
            );
        }
    }

    public static function assets(string $asset): ?string
    {
        // Ensure the asset path starts with a slash
        $asset = ltrim($asset, '/');

        // Define the local path to the assets directory
        $localPath = rtrim(self::plugin_url(), '/') . '/assets/';

        // Return the full asset path
        return $localPath . $asset;
    }

    public static function dist(string $fileName): ?string
    {
        // Ensure the asset path starts with a slash
        $fileName = ltrim($fileName, '/');

        // Define the local path to the assets directory
        $localPath = rtrim(self::plugin_url(), '/') . '/dist/';

        // Return the full asset path
        return $localPath . $fileName;
    }
}

//Global functions to use in twig files

function twint_asset(string $asset): ?string
{
    return TwintPayment::assets($asset);
}

function twint_dist(string $file): ?string
{
    return TwintPayment::dist($file);
}

