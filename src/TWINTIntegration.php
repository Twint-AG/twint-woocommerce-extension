<?php

namespace TWINT;

use TWINT\Abstract\ServiceProvider\DatabaseServiceProvider;
use TWINT\Factory\ClientBuilder;
use TWINT\MetaBox\TwintApiResponseMeta;
use Twint\Sdk\Value\Money;
use Twint\Sdk\Value\UnfiledMerchantTransactionReference;
use TWINT\Services\PaymentService;
use TWINT\Views\SettingsLayoutViewAdapter;
use TWINT\Views\TwigTemplateEngine;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use WC_TWINT_Payments;
use Exception;

defined('ABSPATH') || exit;

/**
 * @author Jimmy
 * @version 0.0.1
 */
class TWINTIntegration
{
    /**
     * @var string
     */
    protected string $page_hook_setting;

    /**
     * @var PaymentService
     */
    public PaymentService $paymentService;

    /**
     * Class constructor.
     */
    public function __construct()
    {
        $GLOBALS['TWIG_TEMPLATE_ENGINE'] = TwigTemplateEngine::INSTANCE();
        add_action('admin_enqueue_scripts', [$this, 'enqueueStyles'], 19);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts'], 20);

        add_action('admin_menu', [$this, 'registerMenuItem']);
        add_action('woocommerce_order_status_processing', [$this, 'wooOrderStatusProcessing'], 10, 6);

        add_filter('woocommerce_locate_template', [$this, 'wooPluginTemplate'], 10, 3);

        new TwintApiResponseMeta();
        $this->paymentService = new PaymentService();
    }

    public function wooPluginTemplate($template, $template_name, $template_path)
    {
        global $woocommerce;
        $_template = $template;
        if (!$template_path) {
            $template_path = $woocommerce->template_url;
        }

        $plugin_path = WC_TWINT_Payments::plugin_abspath() . '/template/woocommerce/';

        // Look within passed path within the theme - this is priority
        $template = locate_template(
            array(
                $template_path . $template_name,
                $template_name
            )
        );

        if (!$template && file_exists($plugin_path . $template_name)) {
            $template = $plugin_path . $template_name;
        }

        if (!$template) {
            $template = $_template;
        }

        return $template;
    }

    public function wooOrderStatusProcessing($orderId): void
    {
        $order = wc_get_order($orderId);

        if ($order->get_payment_method() === 'twint') {
            $order->update_status('wc-pending');
            $this->paymentService->createOrder($order);
        }
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
        wp_enqueue_script('js-woocommerce-gateway-twint', WC_TWINT_Payments::plugin_url() . '/assets/js/twint-payment-integration.js', ['jquery']);
    }

    public function enqueueStyles(): void
    {
        wp_enqueue_style('css-woocommerce-gateway-twint', WC_TWINT_Payments::plugin_url() . '/assets/css/style.css');
    }

    public function adminPluginSettingsLink($links)
    {
        $settings_link = '<a href="' . esc_url('admin.php?page=twint-payment-integration-settings') . '">' . __('Settings', 'woocommerce-gateway-twint') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * STATIC METHODS
     */
    public static function INSTALL(): void
    {
        self::CREATE_DB();
    }

    public static function UNINSTALL(): void
    {
        /**
         * TODO:
         * Do we need to remove the table when deactivating plugin.
         */
        global $wpdb;
        global $table_prefix;
        $tableName = $table_prefix . "twint_transactions_log";

        $wpdb->query("DROP TABLE IF EXISTS " . $tableName);
    }

    public static function CREATE_DB(): void
    {
        $databaseServiceProvider = DatabaseServiceProvider::GET_INSTANCE();

        $resultTransactionsLogTable = $databaseServiceProvider->checkSettingsTableExist('twint_transactions_log');
        if (!$resultTransactionsLogTable) {
            $databaseServiceProvider->createTwintTransactionsLogTable();
        }
    }
}
