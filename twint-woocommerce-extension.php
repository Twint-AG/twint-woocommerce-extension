<?php
/**
 * Plugin Name: TWINT Payment for WooCommerce
 * Plugin URI: https://twint.ch
 * Description: TWINT Payment Plugin for WooCommerce
 * Version: 1.0.0-RC28
 * Author: TWINT
 * Author URI: https://twint.ch
 * Developer: TWINT
 * Developer URI: https://www.twint.ch/en/lp/express-checkout-installation-guide-for-woocommerce/
 * Text Domain: twint-woocommerce-extension
 * Domain Path: /languages
 * Copyright: © 2024 NFQ.
 * License: MIT
 * WC requires at least: 6.0
 * WC tested up to: 9.3
 */


if (!defined('ABSPATH')) {
    exit;
}

require __DIR__ . '/vendor/autoload.php';

use Twint\Woo\Plugin;

Plugin::init(__FILE__);
