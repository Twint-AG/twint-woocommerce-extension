<?php
/**
 * Plugin Name: WooCommerce Twint Payment
 * Plugin URI: https://www.nfq-asia.com/
 * Description: Twint Woocommerce Payment Method is a secure and user-friendly plugin that allows Swiss online merchants to accept payments via Twint, a popular mobile payment solution in Switzerland.
 * Version: 1.0.0
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


if (!defined('ABSPATH')) {
    exit;
}

require __DIR__ . '/vendor/autoload.php';

use Twint\TwintPayment;

TwintPayment::init();
