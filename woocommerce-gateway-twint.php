<?php
/**
 * Plugin Name: TBU - Twint Payment
 * Plugin URI: https://www.nfq-asia.com/
 * Description: TWINT Woocommerce Payment Method is a secure and user-friendly plugin that allows Swiss online merchants to accept payments via TWINT, a popular mobile payment solution in Switzerland.
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
 * WC TWINT Payment gateway plugin class.
 *
 * @class WC_TWINT_Payments
 */
class WC_TWINT_Payments
{
    /**
     * Plugin bootstrapping.
     */
    public static function init(): void
    {
        // TWINT Payments gateway class.
        add_action('plugins_loaded', array(__CLASS__, 'includes'), 0);

        // Make the TWINT Payment gateway available to WC.
        add_filter('woocommerce_payment_gateways', array(__CLASS__, 'add_gateway'));

        // Registers WooCommerce Blocks integration.
        add_action('woocommerce_blocks_loaded', array(__CLASS__, 'woocommerce_gateway_twint_woocommerce_block_support'));

        $instance = new \TWINT\TWINTIntegration();
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($instance, 'adminPluginSettingsLink'));
    }

    /**
     * Add the TWINT Payment gateway to the list of available gateways.
     *
     * @param array $gateways
     * @return array
     */
    public static function add_gateway(array $gateways): array
    {
        if (current_user_can('manage_options')) {
            $gateways[] = 'WC_Gateway_TWINT';
        }
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

        // Make the WC_Gateway_TWINT class available.
        if (class_exists('WC_Payment_Gateway')) {
            require_once 'includes/class-wc-gateway-twint.php';
        }
    }

    /**
     * Plugin url.
     *
     * @return string
     */
    public static function plugin_url(): string
    {
        return untrailingslashit(plugins_url('/', __FILE__));
    }

    /**
     * Plugin url.
     *
     * @return string
     */
    public static function plugin_abspath(): string
    {
        return trailingslashit(plugin_dir_path(__FILE__));
    }

    /**
     * Registers WooCommerce Blocks integration.
     *
     */
    public static function woocommerce_gateway_twint_woocommerce_block_support(): void
    {
        if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
            require_once 'includes/blocks/class-wc-twint-payment-blocks.php';
            add_action(
                'woocommerce_blocks_payment_method_type_registration',
                function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                    $payment_method_registry->register(new WC_Gateway_TWINT_Blocks_Support());
                }
            );
        }
    }
}

WC_TWINT_Payments::init();
