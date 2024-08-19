<?php
/**
 * Plugin Name: WooCommerce Twint Payment
 * Plugin URI: https://www.nfq-asia.com/
 * Description: Twint Woocommerce Payment Method is a secure and user-friendly plugin that allows Swiss online merchants to accept payments via Twint, a popular mobile payment solution in Switzerland.
 * Version: 0.0.1
 * Author: NFQ GROUP
 * Author URI: https://www.nfq-asia.com/
 * Developer:NFQ GROUP
 * Developer URI: https://www.nfq-asia.com/
 * Text Domain: woocommerce-gateway-twint
 * Domain Path: /i18n/languages
 * Copyright: © 2024 NFQ.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * WC requires at least: 6.0
 * WC tested up to: 8.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}
require __DIR__ . '/vendor/autoload.php';

/**
 * WC Twint Payment gateway plugin class.
 *
 * @class WC_Twint_Payments
 */
class WC_Twint_Payments
{
    /**
     * Plugin bootstrapping.
     */
    public static function init(): void
    {
        // Twint Payments gateway class.
        add_action('plugins_loaded', array(__CLASS__, 'includes'), 0);

        // Make the Twint Payment gateway available to WC.
        add_filter('woocommerce_payment_gateways', array(__CLASS__, 'addGateway'));

        // Registers WooCommerce Blocks integration.
        add_action('woocommerce_blocks_loaded', array(__CLASS__, 'woocommerce_gateway_twint_woocommerce_block_support'));

        $instance = new \Twint\Woo\TwintIntegration();
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($instance, 'adminPluginSettingsLink'));

        register_activation_hook(__FILE__, [\Twint\Woo\TwintIntegration::class, 'INSTALL']);
        register_deactivation_hook(__FILE__, [\Twint\Woo\TwintIntegration::class, 'UNINSTALL']);

        add_action('init', [__CLASS__, 'createCustomWooCommerceStatus']);
        add_filter('wc_order_statuses', [__CLASS__, 'addCustomWooCommerceStatusToList']);

        add_action( 'woocommerce_order_refunded', [__CLASS__, 'custom_order_status_after_refund'], 20, 2 );
    }

    public static function custom_order_status_after_refund( $order_id, $refund_id ): void
    {
        $order = wc_get_order( $order_id );

        // Check if the refund was processed by your custom gateway
        if ( $order->get_payment_method() === 'twint_regular' ) {
            // Change the order status
            $order->update_status( 'refunded-partial', __( 'Order status changed to *refunded-partial* after refund.', 'woocommerce' ) );
        }
    }

    public static function createCustomWooCommerceStatus(): void
    {
        register_post_status(
            \WC_Gateway_Twint_Regular_Checkout::getOrderStatusAfterPartiallyRefunded(),
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
        $orderStatuses[\WC_Gateway_Twint_Regular_Checkout::getOrderStatusAfterPartiallyRefunded()] = __('Refunded (partially)', 'woocommerce-gateway-twint');
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
        /**
         * Insert Twint Regular Checkout payment method into the woo payment methods
         */
        $gateways[] = 'WC_Gateway_Twint_Regular_Checkout';

        /**
         * Insert Twint Express Checkout payment method into the woo payment methods
         */
        $gateways[] = 'WC_Gateway_Twint_Express_Checkout';
        return $gateways;
    }

    /**
     * Is plugin active.
     *
     * @param string $plugin Plugin Name.
     * @return  bool
     * @version 0.0.1
     * @since  0.0.1
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

        // Make the WC_Gateway_Twint_Regular_Checkout class available.
        require_once 'includes/class-wc-gateway-twint-regular-checkout.php';

        // Make the WC_Gateway_Twint_Express_Checkout class available.
        require_once 'includes/class-wc-gateway-twint-express-checkout.php';
    }

    /**
     * Plugin url.
     *
     * @return string
     */
    public
    static function plugin_url(): string
    {
        return untrailingslashit(plugins_url('/', __FILE__));
    }

    /**
     * Plugin url.
     *
     * @return string
     */
    public
    static function plugin_abspath(): string
    {
        return trailingslashit(plugin_dir_path(__FILE__));
    }

    /**
     * Registers WooCommerce Blocks integration.
     *
     */
    public
    static function woocommerce_gateway_twint_woocommerce_block_support(): void
    {
        if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
            require_once 'includes/blocks/class-wc-twint-payment-blocks.php';
            add_action(
                'woocommerce_blocks_payment_method_type_registration',
                function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                    $payment_method_registry->register(new WC_Gateway_Twint_Regular_Checkout_Blocks_Support());
                }
            );
        }
    }
}

WC_Twint_Payments::init();
