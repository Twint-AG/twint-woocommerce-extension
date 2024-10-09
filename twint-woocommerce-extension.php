<?php
/**
 * Plugin Name: WooCommerce TWINT Payment
 * Plugin URI: https://twint.ch
 * Description: TWINT Payment Plugin for WooCommerce
 * Version: 1.0.0
 * Author: TWINT
 * Author URI: https://twint.ch
 * Developer: TWINT
 * Developer URI: https://twint.ch
 * Text Domain: woocommerce-gateway-twint
 * Domain Path: /i18n/languages
 * Copyright: © 2024 NFQ.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * WC requires at least: 6.0
 * WC tested up to: 8.0
 */


if (!defined('ABSPATH')) {
    exit;
}

require __DIR__ . '/vendor/autoload.php';

use Twint\Plugin;

Plugin::init(__FILE__);
