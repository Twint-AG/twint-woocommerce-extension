<?php

declare(strict_types=1);

namespace Twint\Woo\Model\Gateway;

use Exception;
use Throwable;
use Twint\Plugin;
use Twint\Woo\Container\Lazy;
use Twint\Woo\Container\LazyLoadTrait;
use Twint\Woo\Model\Modal\Modal;
use Twint\Woo\Model\Pairing;
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

        $this->method_title = __('TWINT Checkout', 'woocommerce-gateway-twint');
        $this->title = __('TWINT', 'woocommerce-gateway-twint');
        $this->method_description = __('Allows TWINT Checkout', 'woocommerce-gateway-twint');

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
                'title' => __('Enable/Disable', 'woocommerce-gateway-twint'),
                'type' => 'checkbox',
                'label' => __('Enable TWINT Checkout', 'woocommerce-gateway-twint'),
                'default' => SettingService::YES,
            ],
            'title' => [
                'title' => __('Title', 'woocommerce-gateway-twint'),
                'type' => 'safe_text',
                'description' => __('This controls the title which the user sees during checkout.', 'woocommerce-gateway-twint'),
                'desc_tip' => true,
                'default' => __('TWINT', 'woocommerce-gateway-twint'),
            ],
        ];
    }

    protected function registerHooks()
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

        $this->modal->registerHooks();
    }

    public function additionalWoocommerceHandlerAfterCheckoutForm(): void
    {
        Plugin::enqueueScript('store-legacy-checkout-modal', '/legacy-regular.js', false);
    }

    /**
     * Set up the status initial for the order first created.
     * @param mixed $status
     * @param mixed $orderId
     * @param mixed $order
     * @since 1.0.0
     */
    public function setCompleteOrderStatus($status, $orderId, $order): string
    {
        if ($order && static::UNIQUE_PAYMENT_ID === $order->get_payment_method()) {
            $status = 'pending-payment';
        }

        return $status;
    }

    public function addOrderPayButton($orderId): void
    {
        $order = wc_get_order($orderId);

        if ('wc-' . $order->get_status() === self::getOrderStatusAfterFirstTimeCreatedOrder()) {
            printf(
                '<a class="woocommerce-button wp-element-button button pay" href="%s">%s</a>',
                $order->get_checkout_payment_url(),
                __('Pay for this order', 'woocommerce')
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
            $currency = get_woocommerce_currency();

            if ($currency !== static::SUPPORTED_CURRENCY) {
                return [
                    'result' => 'Payment method only support for ' . static::SUPPORTED_CURRENCY,
                ];
            }

            if ($this->settingService->isWooUsingBlockVersion()) {
                $order->update_status(self::getOrderStatusAfterFirstTimeCreatedOrder());
            } else {
                $order->update_status('pending');
            }

            $pairing = $this->getPairingRepository()->findByWooOrderId($order->get_id());
            if (!$pairing instanceof Pairing) {
                $apiResponse = $this->getPaymentService()->createOrder($order);
                $this->getPairingService()->create($apiResponse, $order);
            }

            /**
             * Need to reduce stock levels from Order
             */
            wc_reduce_stock_levels($order_id);

            return [
                'result' => 'success',
                'redirect' => false,
                'messages' => __('Thank you. Your order has been received.', 'woocommerce'),
                'thankyouUrl' => $this->get_return_url($order),
                'pairingId' => $pairing->getId(),
                'pairingToken' => $pairing->getToken(),
                'currency' => $order->get_currency(),
                'nonce' => wp_create_nonce('twint_check_pairing_status'),
                'shopName' => get_bloginfo('name'),
                'amount' => number_format(
                    (float) $order->get_total(),
                    (int) get_option('woocommerce_price_num_decimals'),
                    get_option('woocommerce_price_decimal_sep'),
                    get_option('woocommerce_price_thousand_sep')
                ),
            ];
        } catch (Exception $e) {
            $this->logger->error('Twint RegularCheckoutGateway::process_payment ' . PHP_EOL . $e->getMessage(), [
                'orderID' => $order->get_id(),
                'paymentMethod' => $order->get_payment_method(),
            ]);

            throw $e;
        }
    }
}
