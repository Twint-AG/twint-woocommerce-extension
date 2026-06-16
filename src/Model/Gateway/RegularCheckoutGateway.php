<?php

declare(strict_types=1);

namespace Twint\Woo\Model\Gateway;

use Exception;
use Throwable;
use Twint\Woo\Constant\TwintConstant;
use Twint\Woo\Container\Lazy;
use Twint\Woo\Container\LazyLoadTrait;
use Twint\Woo\Model\Modal\Modal;
use Twint\Woo\Plugin;
use Twint\Woo\Repository\PairingRepository;
use Twint\Woo\Service\PairingService;
use Twint\Woo\Service\PaymentService;
use Twint\Woo\Service\SettingService;

/**
 * @method PaymentService getPaymentService()
 * @method PairingService getPairingService()
 * @method PairingRepository getPairingRepository()
 */
class RegularCheckoutGateway extends AbstractGateway
{
    use LazyLoadTrait;

    public const UNIQUE_PAYMENT_ID = 'twint_regular';

    protected static array $lazyLoads = ['pairingRepository', 'paymentService', 'pairingService'];

    public $id = self::UNIQUE_PAYMENT_ID;

    private Modal $modal;

    private Lazy|PairingRepository $pairingRepository;

    private Lazy|PaymentService $paymentService;

    private Lazy|PairingService $pairingService;

    private SettingService $settingService;

    /**
     * Constructor for the gateway.
     */
    public function __construct()
    {
        parent::__construct();

        $this->initAdminConfig();

        $this->modal = Plugin::di('payment.modal');
        $this->pairingRepository = Plugin::di('pairing.repository');
        $this->paymentService = Plugin::di('payment.service');
        $this->pairingService = Plugin::di('pairing.service');
        $this->settingService = Plugin::di('setting.service');

        $this->registerHooks();
    }

    protected function initAdminConfig()
    {
        $this->icon = apply_filters('woocommerce_twint_gateway_regular_icon', '');

        $this->method_title = __('TWINT Checkout', 'twint-woocommerce-extension');
        $this->title = __('TWINT', 'twint-woocommerce-extension');
        $this->method_description = '';

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables.
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
    }

    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields(): void
    {
        $this->form_fields = [
            'enabled' => [
                'title' => __('Enable/Disable', 'twint-woocommerce-extension'),
                'type' => 'checkbox',
                'label' => __('Enable TWINT Checkout', 'twint-woocommerce-extension'),
                'default' => TwintConstant::YES,
            ],
            'title' => [
                'title' => __('Title', 'twint-woocommerce-extension'),
                'type' => 'safe_text',
                'description' => __('This controls the title which the user sees during checkout.', 'twint-woocommerce-extension'),
                'desc_tip' => true,
                'default' => __('TWINT', 'twint-woocommerce-extension'),
            ],
            'description' => [
                'title' => __('Description', 'twint-woocommerce-extension'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'twint-woocommerce-extension'),
                'desc_tip' => true,
                'default' => __('Pay securely with TWINT.', 'twint-woocommerce-extension'),
            ],
        ];
    }

    protected function registerHooks(): void
    {
        // Actions.
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_filter('woocommerce_payment_complete_order_status', [$this, 'setCompleteOrderStatus'], 10, 3);

        /**
         * These 2 (action and filter) for "Pay" for the order in My account > Orders section.
         */
        add_action('woocommerce_view_order', [$this, 'addOrderPayButton']);
        add_filter('woocommerce_valid_order_statuses_for_payment', [$this, 'appendValidStatusForOrderNeedPayment']);

        /**
         * Add JS script into checkout page only
         */
        add_action('woocommerce_after_checkout_form', [$this, 'additionalWoocommerceHandlerAfterCheckoutForm']);

        add_action('wp_enqueue_scripts', static function () {
            // Check if on the WooCommerce 'order-pay' page
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if (is_checkout() && isset($_GET['pay_for_order'])) {
                Plugin::enqueueScript('regular-order-pay', '/order-pay.js', false);
            }
        });

        $this->modal->registerHooks();
    }

    public function additionalWoocommerceHandlerAfterCheckoutForm(): void
    {
        Plugin::enqueueScript('store-legacy-checkout-modal', '/legacy-regular.js', false);
    }

    public function addOrderPayButton($orderId): void
    {
        $order = wc_get_order($orderId);

        if ('wc-' . $order->get_status() === self::getOrderStatusAfterFirstTimeCreatedOrder()) {
            printf(
                esc_html('<a class="woocommerce-button wp-element-button button pay" href="%s">%s</a>'),
                esc_url($order->get_checkout_payment_url()),
                // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- need to match on translated value from core.
                esc_html(__('Pay for this order', 'woocommerce'))
            );
        }
    }

    public function appendValidStatusForOrderNeedPayment($statuses)
    {
        return $statuses;
    }

    /**
     * Process the payment and return the result.
     *
     * @param int $order_id
     * @throws Exception|Throwable
     */
    public function process_payment($order_id): array
    {
        $order = wc_get_order($order_id);
        try {
            if (get_woocommerce_currency() !== static::SUPPORTED_CURRENCY) {
                return [
                    'result' => 'Payment method only support for ' . static::SUPPORTED_CURRENCY,
                ];
            }

            // Cancel all old pairings
            $client = Plugin::di('client.builder', false)->build();
            $this->getPairingService()->cancelRemainingPairings((int) $order_id, $client);

            $apiResponse = $this->getPaymentService()->createOrder($order);
            $pairing = $this->getPairingService()->create($apiResponse, $order);

            // Start monitoring in background
            if (get_option(TwintConstant::CONFIG_CLI_SUPPORT_OPTION) === 'Yes') {
                Plugin::di('monitor.service', false)->status($pairing);
            }

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if (isset($_GET['pay_for_order'])) {
                $url = wc_get_endpoint_url('order-pay', (string) $order_id, wc_get_checkout_url());
                $url = add_query_arg(
                    [
                        'pay_for_order' => 'true',
                        'key' => $order->get_order_key(),
                        'pairing' => $pairing->getId(),
                    ],
                    $url
                );
            } else {
                $url = '';
            }

            return [
                'result' => 'success',
                'redirect' => $url,
                // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- need to match on translated value from core.
                'messages' => __('Thank you. Your order has been received.', 'woocommerce'),
                'thankyouUrl' => $this->get_return_url($order),
                'pairingId' => $pairing->getId(),
                'pairingToken' => $pairing->getToken(),
                'currency' => $order->get_currency(),
                'nonce' => wp_create_nonce('twint_check_pairing_status'),
                'shopName' => get_bloginfo('name'),
                'amount' => wc_price($order->get_total()),
            ];
        } catch (Exception $e) {
            $this->logger->error('TWINT RegularCheckoutGateway::process_payment: ' . PHP_EOL . $e->getMessage(), [
                'orderID' => $order->get_id(),
                'paymentMethod' => $order->get_payment_method(),
            ]);

            throw $e;
        }
    }

    public function validate_title_field($key, $value): string
    {
        return $this->validate_text_field($key, $value);
    }

    public function validate_description_field($key, $value): string
    {
        return $this->validate_textarea_field($key, $value);
    }

    public function validate_enabled_field($key, $value): string
    {
        return $value === 1 || $value === '1' ? TwintConstant::YES : TwintConstant::NO;
    }
}
