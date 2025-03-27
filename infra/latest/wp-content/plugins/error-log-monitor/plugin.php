<?php
/*
Plugin Name: Error Log Monitor
Plugin URI: http://w-shadow.com/blog/2012/07/25/error-log-monitor-plugin/
Description: Adds a Dashboard widget that displays the last X lines from your PHP error log, and can also send you email notifications about newly logged errors.
Version: 1.7.10
Author: Janis Elsts
Author URI: http://w-shadow.com/
Text Domain: error-log-monitor
Requires PHP: 7.4
*/

if ( !defined('ABSPATH') ) {
	return;
}

if ( !function_exists('wsh_elm_fs') ) {

	// Create a helper function for easy SDK access.
	function wsh_elm_fs() {
		global $wsh_elm_fs;

		if ( !isset($wsh_elm_fs) ) {
			//Activate multisite network integration.
			if ( !defined('WP_FS__PRODUCT_2379_MULTISITE') ) {
				define('WP_FS__PRODUCT_2379_MULTISITE', true);
			}
			//Include Freemius SDK.
			require_once dirname(__FILE__) . '/freemius/start.php';
			/** @noinspection PhpUnhandledExceptionInspection */
			$wsh_elm_fs = fs_dynamic_init(array(
				'id'             => '2379',
				'slug'           => 'error-log-monitor',
				'type'           => 'plugin',
				'public_key'     => 'pk_5b9b22d279f81369f3e39d6225e4c',
				'is_premium'     => false,
				'has_addons'     => false,
				'has_paid_plans' => true,
				'menu'           => array(
					'first-path' => 'plugins.php',
					'support'    => false,
				),
				'is_live'        => true,
			));
		}

		return $wsh_elm_fs;
	}

	// Init Freemius.
	wsh_elm_fs();
	// Signal that SDK was initiated.
	do_action('wsh_elm_fs_loaded');

	//Optimization: Run only in the admin and when doing cron jobs.
	if ( !is_admin() && !defined('DOING_CRON') ) {
		return;
	}

	require dirname(__FILE__) . '/scb/load.php';
	require_once dirname(__FILE__) . '/vendor/ajax-wrapper/AjaxWrapper.php';

	function error_log_monitor_autoloader($className) {
		$prefix = 'Elm_';
		$dir = dirname(__FILE__) . '/Elm/';

		//Does the class name start with the prefix?
		if ( substr($className, 0, strlen($prefix)) !== $prefix ) {
			return;
		}

		//File name = class name without the prefix + .php.
		$fileName = $dir . substr($className, strlen($prefix)) . '.php';
		if ( file_exists($fileName) ) {
			include $fileName;
		}
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	spl_autoload_register('error_log_monitor_autoloader');

	function error_log_monitor_init() {
		//Compatibility workaround: Ensure initialisation code is only run once. Plugins that call
		//scb_init() in or after plugins_loaded can cause this to be executed multiple times.
		static $isInitDone = false;
		if ( $isInitDone ) {
			return;
		}
		$isInitDone = true;

		new Elm_Plugin(__FILE__);
	}

	scb_init('error_log_monitor_init');
}