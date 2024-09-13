<?php

declare(strict_types=1);

namespace Twint;

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Automattic\WooCommerce\Utilities\OrderUtil;
use Twint\Woo\Container\ContainerFactory;
use Twint\Woo\Model\Gateway\ExpressCheckoutGateway;
use Twint\Woo\Model\Gateway\RegularCheckoutGateway;
use Twint\Woo\Model\Method\ExpressCheckout;
use Twint\Woo\Model\Method\RegularCheckout;
use Twint\Woo\TwintIntegration;
use WC_Payment_Gateway;

class Plugin
{
    /**
     * Plugin bootstrapping.
     */
    public static function init(): void
    {
        // Twint Payments gateway class.
        add_action('plugins_loaded', [self::class, 'loaded'], 0);

        // Make the Twint Payment gateway available to WC.
        add_filter('woocommerce_payment_gateways', [self::class, 'addPaymentGateways']);

        // Registers WooCommerce Blocks integration.
        add_action('woocommerce_blocks_loaded', [self::class, 'registerCheckoutBlocks']);

        register_activation_hook((new self())->pluginFile(), [TwintIntegration::class, 'install']);
        register_deactivation_hook((new self())->pluginFile(), [TwintIntegration::class, 'uninstall']);

        add_action('init', [self::class, 'createCustomWooCommerceStatus']);
        add_filter('wc_order_statuses', [self::class, 'addCustomWooCommerceStatusToList']);

        // Declare compatibility with WooCommerce HPOS
        add_action('before_woocommerce_init', static function () {
            if (class_exists(OrderUtil::class)) {
                FeaturesUtil::declare_compatibility('custom_order_tables', (new Plugin())->pluginFile());
            }
        });
    }

    public static function di(string $container): mixed
    {
        return ContainerFactory::instance()->get($container);
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
     */
    public static function addPaymentGateways(array $gateways): array
    {
        $currency = get_woocommerce_currency();
        if ($currency !== 'CHF') {
            return $gateways;
        }

        /**
         * Insert TWINT payment methods into the woo payment methods
         */
        $gateways[] = RegularCheckoutGateway::class;
        $gateways[] = ExpressCheckoutGateway::class;

        return $gateways;
    }

    public static function isPluginActivated(string $plugin): bool
    {
        if (!function_exists('is_plugin_active')) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        return is_plugin_active($plugin);
    }

    /**
     * Plugin loaded.
     */
    public static function loaded(): void
    {
        // Check for active plugins.
        if (!self::isPluginActivated('woocommerce/woocommerce.php') || !class_exists(WC_Payment_Gateway::class)) {
            exit;
        }

        $instance = self::di('twint.integration');
        add_filter(
            'plugin_action_links_' . plugin_basename((new self())->pluginFile()),
            [$instance, 'adminPluginSettingsLink']
        );
    }

    /**
     * Plugin url.
     */
    public static function pluginUrl(): string
    {
        return untrailingslashit(plugins_url('/', (new self())->pluginFile()));
    }

    /**
     * Plugin url.
     */
    public static function abspath(): string
    {
        return trailingslashit(plugin_dir_path((new self())->pluginFile()));
    }

    /**
     * Registers WooCommerce Blocks integration.
     */
    public static function registerCheckoutBlocks(): void
    {
        if (class_exists(PaymentMethodRegistry::class)) {
            add_action(
                'woocommerce_blocks_payment_method_type_registration',
                static function (PaymentMethodRegistry $payment_method_registry) {
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
        $localPath = rtrim(self::pluginUrl(), '/') . '/assets/';

        // Return the full asset path
        return $localPath . $asset;
    }

    public static function dist(string $fileName): ?string
    {
        // Ensure the asset path starts with a slash
        $fileName = ltrim($fileName, '/');

        // Define the local path to the assets directory
        $localPath = rtrim(self::pluginUrl(), '/') . '/dist/';

        // Return the full asset path
        return $localPath . $fileName;
    }

    protected function pluginFile(): string
    {
        return __DIR__ . '/../woocommerce-gateway-twint.php';
    }
}
