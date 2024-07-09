<?php
/**
 * Plugin Name: TBU - Twint Payment
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
        add_action('admin_notices', array(__CLASS__, 'woocommerceAddAdminNoticesIfNotSetupCorrectly'));

        $instance = new \Twint\Woo\TwintIntegration();
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($instance, 'adminPluginSettingsLink'));

        register_activation_hook(__FILE__, [\Twint\Woo\TwintIntegration::class, 'INSTALL']);
        register_deactivation_hook(__FILE__, [\Twint\Woo\TwintIntegration::class, 'UNINSTALL']);
    }

    /**
     * Add the Twint Payment gateway to the list of available gateways.
     *
     * @param array $gateways
     * @return array
     */
    public static function addGateway(array $gateways): array
    {
        $gateways[] = 'WC_Gateway_Twint';
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

    public static function woocommerceAddAdminNoticesIfNotSetupCorrectly()
    {
        $settings = new \Twint\Woo\Services\SettingService();
        $msg = '<h3>TWINT Payment</h3>';
        $merchantId = $settings->getMerchantId();
        $needShowNotice = false;
        if (empty($merchantId)) {
            $needShowNotice = true;
            $msg .= 'The Merchant ID is not set up. Please check again';
        } else {
            $certificate = $settings->getCertificate();
            if (empty($certificate)) {
                $needShowNotice = true;
                $msg .= 'The Certificate is not set up. Please check again';
            }
        }

        if ($needShowNotice) {
            $settingsLink = '<a href="' . esc_url('admin.php?page=twint-payment-integration-settings') . '">' . __('Settings', 'woocommerce-gateway-twint') . '</a>';
            $msg .= '. Please click ' . $settingsLink . ' to finish it before doing anything with TWIN Payment.';
            wp_admin_notice($msg,
                [
                    'type' => 'error'
                ]
            );
        }
    }

    /**
     * Plugin includes.
     */
    public
    static function includes(): void
    {
        // Check for active plugins.
        if (!self::isPluginActivated('woocommerce/woocommerce.php')) {
            return;
        }

        // Make the WC_Gateway_Twint class available.
        if (class_exists('WC_Payment_Gateway')) {
            require_once 'includes/class-wc-gateway-twint.php';
        }
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
                    $payment_method_registry->register(new WC_Gateway_Twint_Blocks_Support());
                }
            );
        }
    }
}

WC_Twint_Payments::init();
