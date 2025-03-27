<?php
/**
 * Plugin Name: Extra product options For WooCommerce | Custom Product Addons and Fields
 * Description: Add extra product options in product page.
 * Author:      ThemeHigh
 * Version:     3.3.1
 * Author URI:  https://www.themehigh.com
 * Plugin URI:  https://www.themehigh.com
 * Text Domain: woo-extra-product-options
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 * WC requires at least: 3.0.0
 * WC tested up to: 9.6
 */

if(!defined('ABSPATH')){ exit; }

if (!function_exists('is_woocommerce_active')){
	function is_woocommerce_active(){
	    $active_plugins = (array) get_option('active_plugins', array());
	    if(is_multisite()){
		   $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
	    }
	    return in_array('woocommerce/woocommerce.php', $active_plugins) || array_key_exists('woocommerce/woocommerce.php', $active_plugins) || class_exists('WooCommerce');
	}
}

if(is_woocommerce_active()) {
	if(!class_exists('WEPOF_Extra_Product_Options')){
		class WEPOF_Extra_Product_Options {
			const TEXT_DOMAIN = 'woo-extra-product-options';

			public function __construct(){
				add_action('init', array($this, 'init'));
			}

			public function init() {
				define('THWEPOF_VERSION', '3.3.1');
				!defined('THWEPOF_BASE_NAME') && define('THWEPOF_BASE_NAME', plugin_basename( __FILE__ ));
				!defined('THWEPOF_PATH') && define('THWEPOF_PATH', plugin_dir_path( __FILE__ ));
				!defined('THWEPOF_URL') && define('THWEPOF_URL', plugins_url( '/', __FILE__ ));
				!defined('THWEPOF_ASSETS_URL') && define('THWEPOF_ASSETS_URL', THWEPOF_URL .'assets/');

				$this->load_plugin_textdomain();

				require_once( THWEPOF_PATH . 'includes/class-thwepof.php' );
				THWEPOF::instance();
			}

			public function load_plugin_textdomain(){
				$locale = apply_filters('plugin_locale', get_locale(), self::TEXT_DOMAIN);

				load_textdomain(self::TEXT_DOMAIN, WP_LANG_DIR.'/woo-extra-product-options/'.self::TEXT_DOMAIN.'-'.$locale.'.mo');
				load_plugin_textdomain(self::TEXT_DOMAIN, false, dirname(THWEPOF_BASE_NAME) . '/languages/');
			}
		}
	}
	new WEPOF_Extra_Product_Options();

	add_action( 'before_woocommerce_init', 'thwepof_before_woocommerce_init' ) ;

	function thwepof_before_woocommerce_init() {
	    if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
	        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	    }
	}
}
