<?php

namespace TWINT;

use TWINT\Views\SettingsLayoutViewAdapter;
use TWINT\Views\TwigTemplateEngine;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

defined('ABSPATH') || exit;

/**
 * @author Jimmy
 * @version 0.0.1
 */
class TWINTIntegration
{
    protected string $page_hook_setting;

    /**
     * Class constructor.
     */
    public function __construct()
    {
        $GLOBALS['TWIG_TEMPLATE_ENGINE'] = TwigTemplateEngine::INSTANCE();
        add_action('admin_enqueue_scripts', [$this, 'enqueueStyles'], 19);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts'], 20);

        add_action('admin_menu', [$this, 'registerMenuItem']);
    }

    /**
     * @return void
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function accessSettingsMenuCallback(): void
    {
        $templateArguments['admin_url'] = admin_url();

        $settingsLayout = new SettingsLayoutViewAdapter($templateArguments);
        $settingsLayout->render();
    }

    public function registerMenuItem(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $this->page_hook_setting = add_menu_page(
            esc_html__('Twint Integration', 'twint-payment-integration'),
            esc_html__('Twint Integration', 'twint-payment-integration'),
            'manage_options',
            'twint-payment-integration-settings',
            [$this, 'accessSettingsMenuCallback'],
            '',
            '30.5'
        );
    }

    public function enqueueScripts(): void
    {
    }

    public function enqueueStyles(): void
    {
    }

    public static function GET_WOOCOMMERCE_VERSION(): string
    {
        if (!function_exists('get_plugins')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }

        $pluginFolder = get_plugins('/' . 'woocommerce');
        $pluginFile = 'woocommerce.php';

        return $pluginFolder[$pluginFile]['Version'] ?? 'NULL';
    }

    public function adminPluginSettingsLink($links)
    {
        $settings_link = '<a href="' . esc_url('admin.php?page=twint-payment-integration-settings') . '">' . __('Settings', 'woocommerce-gateway-twint') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}
