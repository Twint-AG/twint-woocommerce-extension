<?php

declare(strict_types=1);

namespace Twint\Woo;

use Twint\Plugin;
use Twint\Woo\Container\Lazy;
use Twint\Woo\Container\LazyLoadTrait;
use Twint\Woo\Model\Gateway\RegularCheckoutGateway;
use Twint\Woo\Repository\PairingRepository;
use Twint\Woo\Service\ApiService;
use Twint\Woo\Service\PaymentService;
use Twint\Woo\Template\Admin\MetaBox\TransactionLogMeta;
use Twint\Woo\Template\Admin\SettingsLayoutViewAdapter;
use Twint\Woo\Template\BeforeThankYouBoxViewAdapter;
use WC_Order;

/**
 * @method PairingRepository getPairingRepository()
 * @method PaymentService getPaymentService()
 */
class TwintIntegration
{
    use LazyLoadTrait;

    protected static array $lazyLoads = ['pairingRepository', 'paymentService'];

    protected string $pageHookSetting;

    public function __construct(
        private Lazy|PaymentService    $paymentService,
        private readonly ApiService    $api,
        private Lazy|PairingRepository $pairingRepository,
    ) {
        add_action('admin_enqueue_scripts', [$this, 'enqueueStyles'], 19);
        add_action('wp_enqueue_scripts', [$this, 'wpEnqueueScriptsFrontend'], 19);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts'], 20);

        add_action('admin_menu', [$this, 'registerMenuItem']);

        add_filter('woocommerce_locate_template', [$this, 'wooPluginTemplate'], 10, 3);

        add_action('woocommerce_before_thankyou', [$this, 'additionalWoocommerceBeforeThankyou'], 20);

        new TransactionLogMeta();

        $this->registerApiActions();
    }

    public static function install(): void
    {
        $installer = Plugin::di('installer', true);
        $installer->install();
    }

    public static function uninstall(): void
    {
        $uninstaller = Plugin::di('uninstaller', true);
        $uninstaller->uninstall();
    }

    private function registerApiActions(): void
    {
        // Admin
        Plugin::di('get_transaction_log.action', true);
        Plugin::di('store_configuration.action', true);

        //Frontend
        Plugin::di('payment_status.action', true);
        Plugin::di('express_checkout.action', true);
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

        $template = new BeforeThankYouBoxViewAdapter($order, Plugin::di('pairing.repository', true));
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

    public function accessSettingsMenuCallback(): void
    {
        $args['admin_url'] = admin_url();

        $adapter = new SettingsLayoutViewAdapter(
            Plugin::di('setting.service', true),
            Plugin::di('credentials.validator', true),
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
            __('TWINT', 'woocommerce-gateway-twint'),
            __('TWINT', 'woocommerce-gateway-twint'),
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
