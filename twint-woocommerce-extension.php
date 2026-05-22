<?php

declare(strict_types=1);

/**
 * Plugin Name: TWINT Payment for WooCommerce
 * Plugin URI: https://twint.ch
 * Description: TWINT Payment Plugin for WooCommerce
 * Version: 1.7.4
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

if (!is_dir(__DIR__ . '/vendor')
    && !is_dir(__DIR__ . '/vendor82')
    && !is_dir(__DIR__ . '/vendor83')
    && !is_dir(__DIR__ . '/vendor84')
    && !is_dir(__DIR__ . '/vendor85')
) {
    add_action('admin_notices', static function () {
        $release_url = 'https://github.com/Twint-AG/twint-woocommerce-extension/releases';
        $message = sprintf(
            __('Please download the TWINT Extension plugin from the <a href="%s">releases</a> page.', 'twint-woocommerce-extension'),
            esc_url($release_url)
        );
        printf('<div class="notice notice-error"><p>%s</p></div>', wp_kses_post($message));
    });

    return;
}

require __DIR__ . '/vendor/autoload.php';

use Twint\Woo\Plugin;
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

Plugin::init(__FILE__);
if (class_exists(PucFactory::class)) {
    $myUpdateChecker = PucFactory::buildUpdateChecker(
        'https://raw.githubusercontent.com/Twint-AG/twint-woocommerce-extension/refs/heads/latest/version.json',
        __FILE__,
        'twint-woocommerce-extension'
    );
}
