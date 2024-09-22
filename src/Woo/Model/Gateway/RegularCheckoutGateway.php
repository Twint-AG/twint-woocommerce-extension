<?php

declare(strict_types=1);

namespace Twint\Woo\Model\Gateway;

use Exception;
use Throwable;
use Twint\Plugin;
use Twint\Woo\Model\Modal\Modal;
use Twint\Woo\Model\Pairing;
use Twint\Woo\Repository\PairingRepository;
use Twint\Woo\Service\PairingService;
use Twint\Woo\Service\PaymentService;
use Twint\Woo\Service\SettingService;

class RegularCheckoutGateway extends AbstractGateway
{
    public const UNIQUE_PAYMENT_ID = 'twint_regular';

    public $id = self::UNIQUE_PAYMENT_ID;

    private Modal $modal;
    private PairingRepository $pairingRepository;
    private PaymentService $paymentService;
    private PairingService $pairingService;

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

        $this->registerHooks();
    }

    protected function registerHooks()
    {
        // Actions.
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_filter('woocommerce_payment_complete_order_status', [$this, 'setCompleteOrderStatus'], 10, 3);

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

        $this->modal->registerHooks();
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

    /**
     * Process the payment and return the result.
     *
     * @param int $order_id
     * @throws Exception
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

            $order->update_status(self::getOrderStatusAfterFirstTimeCreatedOrder());

            /**
             * Need to reduce stock levels from Order
             */
            wc_reduce_stock_levels($order_id);

            // Remove cart
            // TODO Think about this cart
            //            WC()->cart->empty_cart();

            $pairing = Plugin::di('pairing.repository')->findByWooOrderId($order_id);

            return [
                'result' => 'success',
                'redirect' => false,
                'thankyouUrl' => $this->get_return_url($order),
                'pairingId' => $pairing->getId(),
                'pairingToken' => $pairing->getToken(),
                'currency' => $order->get_currency(),
                'nonce' => wp_create_nonce('twint_check_pairing_status'),
                'shopName' => get_bloginfo('name'),
                'amount' => number_format(
                    (float)$order->get_total(),
                    (int)get_option('woocommerce_price_num_decimals'),
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
