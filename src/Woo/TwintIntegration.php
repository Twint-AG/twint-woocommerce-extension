<?php

namespace Twint\Woo;

use Twint\Woo\App\API\ApiService;
use Twint\Woo\App\API\TwintApiWordpressAjax;
use Twint\Woo\CronJob\TwintCancelOrderExpiredCronJob;
use Twint\Woo\MetaBox\TransactionLogMeta;
use Twint\Woo\Migrations\CreateTwintPairingTable;
use Twint\Woo\Migrations\CreateTwintTransactionLogTable;
use Twint\Woo\Services\PairingService;
use Twint\Woo\Services\PaymentService;
use Twint\Woo\Services\SettingService;
use Twint\Woo\Templates\BeforeThankYouBoxViewAdapter;
use Twint\Woo\Templates\SettingsLayoutViewAdapter;
use WC_Twint_Payments;

defined('ABSPATH') || exit;

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
     * @var PairingService
     */
    private PairingService $pairingService;

    /**
     * @var ApiService
     */
    private ApiService $apiService;

    /**
     * Class constructor.
     */
    public function __construct()
    {
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

        add_action('woocommerce_refund_created', [$this, 'refundCreatedHandler'], 10);

        new TransactionLogMeta();
        new TwintApiWordpressAjax();
        new TwintCancelOrderExpiredCronJob();
        $this->paymentService = new PaymentService();
        $this->pairingService = new PairingService();
        $this->apiService = new ApiService();
    }

    /**
     * @throws \Throwable
     */
    public function refundCreatedHandler($refundId): void
    {
        $refund = wc_get_order($refundId);
        $order = wc_get_order($refund->get_parent_id());
        $amount = $refund->get_amount();

        $apiResponse = $this->paymentService->reverseOrder($order, $amount, $refundId);

        // Check if the refund was processed by your custom gateway
        if ($order->get_payment_method() === \WC_Gateway_Twint_Regular_Checkout::getId()) {
            $remainingAmountRefunded = $order->get_remaining_refund_amount();
            if ($remainingAmountRefunded > 0) {
                $status = \WC_Gateway_Twint_Regular_Checkout::getOrderStatusAfterPartiallyRefunded();
            } else {
                $status = 'wc-refunded';
            }

            $order->update_status($status);
        }

        $pairing = $this->pairingService->findByWooOrderId($order->get_id());
        $log['pairing_id'] = $pairing->getId();
        $log['order_id'] = $order->get_id();
        $log['order_status'] = $order->get_status();
        $log['transaction_id'] = $order->get_transaction_id();
        $log = array_merge($log, $apiResponse->getLog());
        $this->apiService->saveLog($log);
    }

    public function wooBeforeOrderUpdateChange($orderId, $items): void
    {
//        $order = wc_get_order($orderId);
//        if ($order->get_payment_method() === 'twint_regular') {
//
//            $oldStatus = $items['original_post_status'];
//            $newStatus = $items['order_status'];
//
//            if (in_array($oldStatus, ['wc-pending', 'pending']) && $oldStatus !== $newStatus) {
//                if ($newStatus !== 'refunded-partially') {
//                    /**
//                     * Save order note for admin to know that why the order's status can not be changed.
//                     */
//                    $note = __(
//                        'The order status can not be changed from <strong>' . wc_get_order_status_name($oldStatus) . '</strong> to <strong>' . wc_get_order_status_name($newStatus) . '</strong>, because this order has not been paid by the customer.',
//                        'woocommerce-gateway-twint'
//                    );
//                    $order->add_order_note($note);
//
//                    $_POST['order_status'] = 'wc-pending';
//                    $_POST['post_status'] = 'wc-pending';
//                    $_POST['original_post_status'] = 'wc-pending';
//                }
//            }
//        }
    }

    public function additionalWoocommerceBeforeThankyou(\WC_Order|int $order): void
    {
        if (is_int($order)) {
            $orderId = $order;
            $order = wc_get_order($orderId);
        }

        if ($order->get_payment_method() !== \WC_Gateway_Twint_Regular_Checkout::getId()) {
            return;
        }

        $template = new BeforeThankYouBoxViewAdapter($order);
        $template->render();
    }

    public function wpEnqueueScriptsFrontend(): void
    {
        wp_enqueue_script('js-woocommerce-gateway-twint-frontend', twint_dist('/frontend/frontstore.js'));
        wp_enqueue_script('js-woocommerce-gateway-DeviceSwitcher', twint_dist('/DeviceSwitcher.js'));
        wp_enqueue_script('js-woocommerce-gateway-PaymentStatusRefresh', twint_dist('/PaymentStatusRefresh.js'));
        wp_enqueue_script('js-woocommerce-gateway-ModalQR', twint_dist('/ModalQR.js'));

        wp_localize_script('js-woocommerce-gateway-twint-frontend', 'twint_api', [
            'admin_url' => admin_url('admin-ajax.php')
        ]);

        wp_enqueue_style('css-woocommerce-gateway-twint-frontend', twint_dist('/frontend.css'));
    }

    public function additionalSingleProductButton(): void
    {
        global $product;
        // TODO display Express Checkout
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

    /**
     * @throws \Throwable
     */
    public function woocommerceCheckoutOrderCreated($orderId): void
    {
        $order = wc_get_order($orderId);

        if ($order->get_payment_method() === \WC_Gateway_Twint_Regular_Checkout::getId()) {
            $pairing = $this->pairingService->findByWooOrderId($order->get_id());
            if (!$pairing) {
                $apiResponse = $this->paymentService->createOrder($order);
                $res = $this->pairingService->create($apiResponse, $order);
            }
        }
    }

    /**
     * @return void
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
            __('TWINT Settings', 'woocommerce-gateway-twint'),
            __('TWINT Settings', 'woocommerce-gateway-twint'),
            'manage_options',
            'twint-payment-integration-settings',
            [$this, 'accessSettingsMenuCallback'],
            twint_assets('/images/twint_icon.svg'),
            '30.5'
        );
    }

    public function enqueueScripts(): void
    {
        wp_enqueue_script('js-woocommerce-gateway-twint-CredentialSetting', WC_Twint_Payments::plugin_url() . '/dist/CredentialSetting.js', ['jquery']);

        wp_localize_script('js-woocommerce-gateway-twint-CredentialSetting', 'twint_api', [
            'admin_url' => admin_url('admin-ajax.php')
        ]);
        wp_enqueue_script('js-woocommerce-gateway-twint', WC_Twint_Payments::plugin_url() . '/dist/TwintPaymentIntegration.js', ['jquery']);
    }

    public function enqueueStyles(): void
    {
        wp_enqueue_style('css-woocommerce-gateway-twint', WC_Twint_Payments::plugin_url() . '/dist/admin.css');
    }

    public function adminPluginSettingsLink($links)
    {
        $settings_link = '<a href="' . esc_url('admin.php?page=twint-payment-integration-settings') . '">' . __('Settings', 'woocommerce-gateway-twint') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    public static function install(): void
    {
        self::createDatabase();

        TwintCancelOrderExpiredCronJob::initCronjob();

        $pluginLanguagesPath = \WC_Twint_Payments::plugin_abspath() . 'i18n/languages/';
        $wpLangPluginPath = WP_CONTENT_DIR . '/languages/plugins/';
        $pluginLanguagesDirectory = array_diff(scandir($pluginLanguagesPath), ['..', '.']);
        foreach ($pluginLanguagesDirectory as $language) {
            \Psl\Filesystem\copy($pluginLanguagesPath . $language, $wpLangPluginPath . $language, true);
        }

        // Init setting for payment gateway
        $initData = [
            'enabled' => 'yes',
            'title' => 'TWINT',
        ];
        update_option('woocommerce_twint_regular_settings', $initData);
    }

    public static function uninstall(): void
    {
        /**
         * Do we need to remove the table when deactivating plugin?
         */
        if (SettingService::getAutoRemoveDBTableWhenDisabling() === 'yes') {
            CreateTwintTransactionLogTable::down();
            CreateTwintPairingTable::down();
        }

        TwintCancelOrderExpiredCronJob::removeCronjob();
    }

    public static function createDatabase(): void
    {
        CreateTwintTransactionLogTable::up();
        CreateTwintPairingTable::up();
    }
}
