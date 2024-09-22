<?php

declare(strict_types=1);

namespace Twint\Woo;

use Throwable;
use Twint\Plugin;
use Twint\Woo\Model\Gateway\RegularCheckoutGateway;
use Twint\Woo\Model\Pairing;
use Twint\Woo\Repository\PairingRepository;
use Twint\Woo\Service\ApiService;
use Twint\Woo\Service\PairingService;
use Twint\Woo\Service\PaymentService;
use Twint\Woo\Template\Admin\MetaBox\TransactionLogMeta;
use Twint\Woo\Template\Admin\SettingsLayoutViewAdapter;
use Twint\Woo\Template\BeforeThankYouBoxViewAdapter;
use WC_Order;

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

        //TODO move belows add_action to Gateway classes.We dont handle for payment here
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

        add_action('woocommerce_before_thankyou', [$this, 'additionalWoocommerceBeforeThankyou'], 20);

        add_action('woocommerce_refund_created', [$this, 'refundCreatedHandler'], 10);

        new TransactionLogMeta();

        $this->registerApiActions();
    }

    public static function install(): void
    {
        $installer = Plugin::di('installer');
        $installer->install();
    }

    public static function uninstall(): void
    {
        $uninstaller = Plugin::di('uninstaller');
        $uninstaller->uninstall();
    }

    private function registerApiActions(): void
    {
        // Admin
        Plugin::di('get_transaction_log.action');
        Plugin::di('store_configuration.action');

        //Frontend
        Plugin::di('payment_status.action');
        Plugin::di('express_checkout.action');
    }

    /**
     * //TODO move gateway class
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
        wp_localize_script('js-woocommerce-gateway-twint-frontend', 'twint_api', [
            'admin_url' => admin_url('admin-ajax.php'),
        ]);

        wp_enqueue_style('css-woocommerce-gateway-twint-frontend', Plugin::dist('/frontend.css'));
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
        Plugin::enqueueScript('admin-credentials', '/credentials-setting.js', false);
        Plugin::enqueueScript('admin-utilities', '/admin-utilities.js', false);

        wp_localize_script('woocommerce-gateway-twint-admin-credentials', 'twint_api', [
            'admin_url' => admin_url('admin-ajax.php'),
        ]);
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
