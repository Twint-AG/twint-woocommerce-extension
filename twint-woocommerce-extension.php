<?php
/**
 * Plugin Name: TWINT Payment for WooCommerce
 * Plugin URI: https://twint.ch
 * Description: TWINT Payment Plugin for WooCommerce
 * Version: 1.5.2
 * Author: TWINT
 * Author URI: https://twint.ch
 * Developer: TWINT
 * Developer URI: https://www.twint.ch/en/lp/express-checkout-installation-guide-for-woocommerce/
 * Text Domain: twint-woocommerce-extension
 * Domain Path: /languages
 * Copyright: © 2024 NFQ.
 * License: MIT
 * Requires PHP: 8.1
 * WC requires at least: 6.0
 * WC tested up to: 9.3
 */


if (!defined('ABSPATH')) {
    exit;
}

require __DIR__ . '/vendor/autoload.php';

use Twint\Woo\Plugin;
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

Plugin::init(__FILE__);
$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://raw.githubusercontent.com/Twint-AG/twint-woocommerce-extension/refs/heads/latest/version.json',
    __FILE__,
    'twint-woocommerce-extension'
);
