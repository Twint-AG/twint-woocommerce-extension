<?php

declare(strict_types=1);

namespace Twint\Woo;

use Throwable;
use Twint\Plugin;
use Twint\Woo\CronJob\MonitorPairingCronJob;
use Twint\Woo\Migration\CreateTwintPairingTable;
use Twint\Woo\Migration\CreateTwintTransactionLogTable;
use Twint\Woo\Model\Gateway\RegularCheckoutGateway;
use Twint\Woo\Model\Pairing;
use Twint\Woo\Repository\PairingRepository;
use Twint\Woo\Service\ApiService;
use Twint\Woo\Service\PairingService;
use Twint\Woo\Service\PaymentService;
use Twint\Woo\Service\SettingService;
use Twint\Woo\Template\Admin\MetaBox\TransactionLogMeta;
use Twint\Woo\Template\Admin\SettingsLayoutViewAdapter;
use Twint\Woo\Template\BeforeThankYouBoxViewAdapter;
use WC_Order;
use function Psl\Filesystem\copy;

class TwintIntegration
{
    protected string $pageHookSetting;

    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly PairingService $pairingService,
        private readonly ApiService $api,
        private readonly PairingRepository $pairingRepository,
    ) {
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
        //        new MonitorPairingCronJob();

        $this->registerApiActions();
    }

    public static function install(): void
    {
        self::createDatabase();

        MonitorPairingCronJob::initCronjob();

        $pluginLanguagesPath = Plugin::abspath() . 'i18n/languages/';
        $wpLangPluginPath = WP_CONTENT_DIR . '/languages/plugins/';
        $pluginLanguagesDirectory = array_diff(scandir($pluginLanguagesPath), ['..', '.']);
        foreach ($pluginLanguagesDirectory as $language) {
            copy($pluginLanguagesPath . $language, $wpLangPluginPath . $language, true);
        }

        // Init setting for payment gateway
        $initData = [
            'enabled' => 'yes',
            'title' => 'TWINT',
        ];
        update_option('woocommerce_twint_regular_settings', $initData);
    }

    public static function createDatabase(): void
    {
        CreateTwintTransactionLogTable::up();
        CreateTwintPairingTable::up();
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

        MonitorPairingCronJob::removeCronjob();
    }

    private function registerApiActions(): void
    {
        // Admin
        Plugin::di('get_transaction_log.action');
        Plugin::di('store_configuration.action');

        //Frontend
        Plugin::di('monitor_pairing.action');
    }

    /**
     * @throws Throwable
     */
    public function refundCreatedHandler($refundId): void
    {
        $refund = wc_get_order($refundId);
        $order = wc_get_order($refund->get_parent_id());
        $amount = $refund->get_amount();

        $apiResponse = $this->paymentService->reverseOrder($order, $amount, $refundId);

        // Check if the refund was processed by your custom gateway
        if ($order->get_payment_method() === RegularCheckoutGateway::getId()) {
            $remainingAmountRefunded = $order->get_remaining_refund_amount();
            if ($remainingAmountRefunded > 0) {
                $status = RegularCheckoutGateway::getOrderStatusAfterPartiallyRefunded();
            } else {
                $status = 'wc-refunded';
            }

            $order->update_status($status);
        }

        $pairing = $this->pairingRepository->findByWooOrderId($order->get_id());
        $log['pairing_id'] = $pairing->getId();
        $log['order_id'] = $order->get_id();
        $log['order_status'] = $order->get_status();
        $log['transaction_id'] = $order->get_transaction_id();
        $log = array_merge($log, $apiResponse->getLog());
        $this->api->saveLog($log);
    }

    public function wooBeforeOrderUpdateChange($orderId, $items): void
    {
    }

    public function additionalWoocommerceBeforeThankyou(WC_Order|int $order): void
    {
        if (is_int($order)) {
            $orderId = $order;
            $order = wc_get_order($orderId);
        }

        if ($order->get_payment_method() !== RegularCheckoutGateway::getId()) {
            return;
        }

        $template = new BeforeThankYouBoxViewAdapter($order, Plugin::di('pairing.repository'));
        $template->render();
    }

    public function wpEnqueueScriptsFrontend(): void
    {
        wp_enqueue_script('js-woocommerce-gateway-twint-frontend', Plugin::dist('/frontend/frontstore.js'));
        wp_enqueue_script('js-woocommerce-gateway-DeviceSwitcher', Plugin::dist('/DeviceSwitcher.js'));
        wp_enqueue_script('js-woocommerce-gateway-PaymentStatusRefresh', Plugin::dist('/PaymentStatusRefresh.js'));
        wp_enqueue_script('js-woocommerce-gateway-ModalQR', Plugin::dist('/ModalQR.js'));

        wp_localize_script('js-woocommerce-gateway-twint-frontend', 'twint_api', [
            'admin_url' => admin_url('admin-ajax.php'),
        ]);

        wp_enqueue_style('css-woocommerce-gateway-twint-frontend', Plugin::dist('/frontend.css'));
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

        $plugin_path = Plugin::abspath() . '/template/woocommerce/';

        // Look within passed path within the theme - this is priority
        $template = locate_template([$template_path . $template_name, $template_name]);

        if (!$template && file_exists($plugin_path . $template_name)) {
            $template = $plugin_path . $template_name;
        }

        if (!$template) {
            $template = $_template;
        }

        return $template;
    }

    /**
     * @throws Throwable
     */
    public function woocommerceCheckoutOrderCreated($orderId): void
    {
        $order = wc_get_order($orderId);

        if ($order->get_payment_method() === RegularCheckoutGateway::getId()) {
            $pairing = $this->pairingRepository->findByWooOrderId($order->get_id());
            if (!$pairing instanceof Pairing) {
                $apiResponse = $this->paymentService->createOrder($order);
                $res = $this->pairingService->create($apiResponse, $order);
            }
        }
    }

    public function accessSettingsMenuCallback(): void
    {
        $args['admin_url'] = admin_url();

        $adapter = new SettingsLayoutViewAdapter(
            Plugin::di('setting.service'),
            Plugin::di('credentials.validator'),
            $args
        );

        $adapter->render();
    }

    public function registerMenuItem(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $this->pageHookSetting = add_menu_page(
            __('TWINT Settings', 'woocommerce-gateway-twint'),
            __('TWINT Settings', 'woocommerce-gateway-twint'),
            'manage_options',
            'twint-payment-integration-settings',
            [$this, 'accessSettingsMenuCallback'],
            Plugin::assets('/images/twint_icon.svg'),
            '30.5'
        );
    }

    public function enqueueScripts(): void
    {
        wp_enqueue_script(
            'js-woocommerce-gateway-twint-CredentialSetting',
            Plugin::dist('/CredentialSetting.js'),
            ['jquery']
        );

        wp_localize_script('js-woocommerce-gateway-twint-CredentialSetting', 'twint_api', [
            'admin_url' => admin_url('admin-ajax.php'),
        ]);
        wp_enqueue_script('js-woocommerce-gateway-twint', Plugin::dist('/TwintPaymentIntegration.js'), ['jquery']);
    }

    public function enqueueStyles(): void
    {
        wp_enqueue_style('css-woocommerce-gateway-twint', Plugin::dist('/admin.css'));
    }

    public function adminPluginSettingsLink($links)
    {
        $link = sprintf(
            '<a href="%s">%s</a>',
            esc_url('admin.php?page=twint-payment-integration-settings'),
            __('Settings', 'woocommerce-gateway-twint')
        );

        array_unshift($links, $link);

        return $links;
    }
}
