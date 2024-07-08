<?php

namespace Twint\Woo;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twint\Woo\Abstract\ServiceProvider\DatabaseServiceProvider;
use Twint\Woo\App\API\TwintApiWordpressAjax;
use Twint\Woo\CronJob\TwintCancelOrderExpiredCronJob;
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

        /**
         * @support Classic Checkout
         * Would be triggered after order created with CLASSIC Checkout
         */
        add_action('woocommerce_checkout_order_created', [$this, 'woocommerceCheckoutOrderCreated']);

        /**
         * @support Block Checkout
         * Would be triggered after order created with BLOCK Checkout
         */
        add_action('woocommerce_store_api_checkout_order_processed', [$this, 'woocommerceCheckoutOrderCreated']);

        add_filter('woocommerce_before_save_order_items', [$this, 'wooBeforeOrderUpdateChange'], 10, 2);

        add_filter('woocommerce_locate_template', [$this, 'wooPluginTemplate'], 10, 3);

        add_action('woocommerce_after_add_to_cart_button', [$this, 'additionalSingleProductButton'], 20);

        add_action('woocommerce_before_thankyou', [$this, 'additionalWoocommerceBeforeThankyou'], 20);

        new TwintApiResponseMeta();
        new TwintApiWordpressAjax();
        new TwintCancelOrderExpiredCronJob();
        $this->paymentService = new PaymentService();
    }

    public function wooBeforeOrderUpdateChange($orderId, $items): void
    {
        $order = wc_get_order($orderId);
        if ($order->get_payment_method() !== 'twint') {
            return;
        }

        $oldStatus = $items['original_post_status'];
        $newStatus = $items['order_status'];

        if (in_array($oldStatus, ['wc-pending', 'pending']) && $oldStatus !== $newStatus) {
            /**
             * Save order note for admin to know that why the order's status can not be changed.
             */
            $note = __(
                'The order status can not be change from <strong>' . wc_get_order_status_name($oldStatus) . '</strong> to <strong>' . wc_get_order_status_name($newStatus) . '</strong>, because this order has not been paid by the customer.',
                'woocommerce-gateway-twint'
            );
            $order->add_order_note($note);

            $_POST['order_status'] = 'wc-pending';
            $_POST['post_status'] = 'wc-pending';
            $_POST['original_post_status'] = 'wc-pending';
        }
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function additionalWoocommerceBeforeThankyou(\WC_Order|int $order): void
    {
        if (is_int($order)) {
            $orderId = $order;
            $order = wc_get_order($orderId);
        }

        if ($order->get_payment_method() !== 'twint') {
            return;
        }

        $template = $this->template->load('Layouts/QrCode.html.twig');
        $twintApiResponse = json_decode($order->get_meta('twint_api_response'), true);
        $data = [
            'orderId' => $order->get_id(),
        ];
        $nonce = wp_create_nonce('twint_check_order_status');
        if ($twintApiResponse) {

            if (!empty($_GET['twint_order_paid'])) {
                $isOrderPaid = true;
            } else {
                if ($order->get_status() === \WC_Gateway_Twint::getOrderStatusAfterPaid()) {
                    $isOrderPaid = true;
                } else {
                    $isOrderPaid = false;
                }
            }

            $isOrderCancelled = false;
            if (!empty($_GET['twint_order_cancelled']) && filter_var($_GET['twint_order_cancelled'], FILTER_VALIDATE_BOOLEAN)) {
                $isOrderCancelled = true;
            }

            $data = array_merge($data, [
                'isOrderPaid' => $isOrderPaid,
                'isOrderCancelled' => $isOrderCancelled,
                'nonce' => $nonce,
            ]);

            if (!$isOrderPaid) {
                $pairingToken = (string)($twintApiResponse['pairingToken'] ?? '');
                $payLinks = $this->paymentService->getPayLinks($pairingToken);
                $options = new QROptions(
                    [
                        'eccLevel' => QRCode::ECC_L,
                        'outputType' => QRCode::OUTPUT_MARKUP_SVG,
                        'version' => 5,
                    ]
                );
                $qrcode = (new QRCode($options))->render($pairingToken);

                $data = array_merge($data, [
                    'qrCode' => $qrcode,
                    'pairingToken' => $pairingToken,
                    'amount' => $order->get_total(),
                    'currency' => $order->get_currency(),
                    'payLinks' => $payLinks,
                ]);
            }
        }

        echo $template->render($data);
    }

    public function wpEnqueueScriptsFrontend(): void
    {
        wp_enqueue_script('js-woocommerce-gateway-twint-frontend', twint_assets('/js/frontend/frontstore.js'));
        wp_enqueue_script('js-woocommerce-gateway-DeviceSwitcher', twint_assets('/js/DeviceSwitcher.js'));
        wp_enqueue_script('js-woocommerce-gateway-PaymentStatusRefresh', twint_assets('/js/PaymentStatusRefresh.js'));

        wp_localize_script('js-woocommerce-gateway-twint-frontend', 'twint_api', [
            'admin_url' => admin_url('admin-ajax.php')
        ]);

        wp_enqueue_style('css-woocommerce-gateway-twint-frontend-core', WC_Twint_Payments::plugin_url() . '/assets/css/core.css');
        wp_enqueue_style('css-woocommerce-gateway-twint-frontend', WC_Twint_Payments::plugin_url() . '/assets/css/frontend_twint.css');
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function additionalSingleProductButton(): void
    {
        global $product;

        $template = $this->template->load('Layouts/components/button.html.twig');
        echo $template->render();
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

    public function woocommerceCheckoutOrderCreated($orderId): void
    {
        $order = wc_get_order($orderId);

        if ($order->get_payment_method() === 'twint') {
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
        wp_enqueue_script('js-woocommerce-gateway-twint', WC_Twint_Payments::plugin_url() . '/assets/js/TwintPaymentIntegration.js', ['jquery']);
    }

    public function enqueueStyles(): void
    {
        wp_enqueue_style('css-woocommerce-gateway-twint-frontend-core', WC_Twint_Payments::plugin_url() . '/assets/css/core.css');
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

        TwintCancelOrderExpiredCronJob::INIT_CRONJOB();
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

        TwintCancelOrderExpiredCronJob::REMOVE_CRONJOB();
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
