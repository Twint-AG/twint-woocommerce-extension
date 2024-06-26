<?php

namespace Twint\Woo;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twint\Woo\Abstract\ServiceProvider\DatabaseServiceProvider;
use Twint\Woo\MetaBox\TwintApiResponseMeta;
use Twint\Woo\Services\PaymentService;
use Twint\Woo\Templates\SettingsLayoutViewAdapter;
use Twint\Woo\Templates\TwigTemplateEngine;
use WC_Twint_Payments;

defined('ABSPATH') || exit;

/**
 * @author Jimmy
 * @version 0.0.1
 */
class TwintIntegration
{
    public const PAGE_TWINT_ORDER_PAYMENT_SLUG = 'twint-order-payment';
    /**
     * @var string
     */
    protected string $page_hook_setting;

    /**
     * @var PaymentService
     */
    public PaymentService $paymentService;

    /**
     * @var \Twig\Environment
     */
    private \Twig\Environment $template;

    /**
     * Class constructor.
     */
    public function __construct()
    {
        $GLOBALS['TWIG_TEMPLATE_ENGINE'] = TwigTemplateEngine::INSTANCE();
        $this->template = $GLOBALS['TWIG_TEMPLATE_ENGINE'];
        add_action('admin_enqueue_scripts', [$this, 'enqueueStyles'], 19);
        add_action('wp_enqueue_scripts', [$this, 'wpEnqueueScriptsFrontend'], 19);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts'], 20);

        add_action('admin_menu', [$this, 'registerMenuItem']);
        add_action('woocommerce_order_status_processing', [$this, 'wooOrderStatusProcessing'], 10, 6);

        add_filter('woocommerce_locate_template', [$this, 'wooPluginTemplate'], 10, 3);

        add_action('woocommerce_after_add_to_cart_button', [$this, 'additionalSingleProductButton'], 20);

        add_action('woocommerce_before_thankyou', [$this, 'additionalWoocommerceBeforeThankyou'], 20);
        add_filter('woocommerce_thankyou_order_received_text', [$this, 'changeWoocommerceThankyouOrderReceivedText'], 10, 2);

        add_shortcode('shortcode_order_twint_payment', [$this, 'shortcodeOrderTwintPayment']);

        new TwintApiResponseMeta();
        $this->paymentService = new PaymentService();
    }

    public function changeWoocommerceThankyouOrderReceivedText($text, $order): string
    {
        if ($order->get_payment_method() !== 'twint') {
            return esc_html(__('Thank you. Your order has been received. Please take a look and use TWINT app to pay your order.', 'woocommerce-gateway-twint'));
        }

        return $text;
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function additionalWoocommerceBeforeThankyou(int $orderId): void
    {
        $order = wc_get_order($orderId);
        $template = $this->template->load('Layouts/QrCode.html.twig');
        $twintApiResponse = json_decode($order->get_meta('twint_api_response'), true);
        $data = [];
        $payLinks = $this->paymentService->getPayLinks($pairingToken ?? '');
        if ($twintApiResponse) {
            $options = new QROptions(
                [
                    'eccLevel' => QRCode::ECC_L,
                    'outputType' => QRCode::OUTPUT_MARKUP_SVG,
                    'version' => 5,
                ]
            );
            $pairingToken = (string)($twintApiResponse['pairingToken'] ?? '');
            $qrcode = (new QRCode($options))->render($pairingToken);

            $data = [
                'qrCode' => $qrcode,
                'pairingToken' => $pairingToken,
                'amount' => $order->get_total(),
                'currency' => $order->get_currency(),
                'payLinks' => $payLinks,
            ];
        }

        echo $template->render($data);
    }

    public function wpEnqueueScriptsFrontend(): void
    {
        wp_enqueue_script('js-woocommerce-gateway-twint-frontend', twint_assets('/js/twint_frontend.js'));

        wp_enqueue_style('css-woocommerce-gateway-twint-frontend-core', WC_Twint_Payments::plugin_url() . '/assets/css/core.css');
        wp_enqueue_style('css-woocommerce-gateway-twint-frontend', WC_Twint_Payments::plugin_url() . '/assets/css/frontend_twint.css');
    }

    public function additionalSingleProductButton(): void
    {
        global $product;

        $name = esc_html("TWINT Express Checkout", "woocommerce"); // <== Here set button name
        $class = 'button alt';
        $style = 'display: inline-block; margin-top: 12px;';

        // Output
        echo '<br><a rel="no-follow" href="#" class="' . $class . '" style="' . $style . '">' . $name . '</a>';
    }

    public function shortcodeOrderTwintPayment(): void
    {
        wc_get_template('payment/twint/payment.php');
    }

    public function wooPluginTemplate($template, $template_name, $template_path)
    {
        global $woocommerce;
        $_template = $template;
        if (!$template_path) {
            $template_path = $woocommerce->template_url;
        }

        $plugin_path = WC_Twint_Payments::plugin_abspath() . '/template/woocommerce/';

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
        wp_enqueue_script('js-woocommerce-gateway-twint', WC_Twint_Payments::plugin_url() . '/assets/js/twint-payment-integration.js', ['jquery']);
    }

    public function enqueueStyles(): void
    {
        wp_enqueue_style('css-woocommerce-gateway-twint', WC_Twint_Payments::plugin_url() . '/assets/css/style.css');
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

        self::CREATE_PAYMENT_QR_PAGE();
    }

    public static function CREATE_PAYMENT_QR_PAGE(): void
    {
        $orderTwintPaymentPage = array(
            'post_type' => 'page',
            'post_title' => 'Twint Order Payment',
            'post_content' => '[shortcode_order_twint_payment]',
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
            'post_name' => self::PAGE_TWINT_ORDER_PAYMENT_SLUG,
        );

        if (!get_page_by_path(self::PAGE_TWINT_ORDER_PAYMENT_SLUG, OBJECT, 'page')) {
            $orderTwintPaymentPageId = wp_insert_post($orderTwintPaymentPage);
            wc_get_logger()->info(
                'wc_twint_order_payment_page',
                [
                    'id' => $orderTwintPaymentPageId,
                    'data' => $orderTwintPaymentPage,
                ]
            );
        }
    }

    public static function UNINSTALL(): void
    {
        /**
         * TODO:
         * Do we need to remove the table when deactivating plugin.
         */
//        global $wpdb;
//        global $table_prefix;
//        $tableName = $table_prefix . "twint_transactions_log";
//
//        $wpdb->query("DROP TABLE IF EXISTS " . $tableName);

        $orderTwintPaymentPage = get_page_by_path(
            self::PAGE_TWINT_ORDER_PAYMENT_SLUG,
            OBJECT,
            'page'
        );
        if ($orderTwintPaymentPage) {
            wp_delete_post($orderTwintPaymentPage->ID, true);
        }
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
