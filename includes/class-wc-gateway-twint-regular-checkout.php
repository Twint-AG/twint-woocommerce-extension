<?php
/**
 * WC_Gateway_Twint_Regular_Checkout class
 *
 * @package  WooCommerce Twint Payment Gateway
 * @since    1.0.0
 */

// Exit if accessed directly.
use chillerlan\QRCode\QRCode;
use Twint\Woo\Services\PairingService;
use Twint\Woo\Services\SettingService;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Twint WC_Gateway_Twint_Regular_Checkout.
 *
 * @class WC_Gateway_Twint_Regular_Checkout
 * @version  1.0.0
 */
class WC_Gateway_Twint_Regular_Checkout extends WC_Payment_Gateway
{
    const UNIQUE_PAYMENT_ID = 'twint_regular';
    /**
     * Payment gateway instructions.
     * @var string
     */
    protected string $instructions;

    /**
     * Unique id for the gateway.
     * @var string
     *
     */
    public $id = self::UNIQUE_PAYMENT_ID;

    public static array $amount_supported = ['CHF'];

    public static function getId(): string
    {
        return self::UNIQUE_PAYMENT_ID;
    }

    /**
     * Constructor for the gateway.
     */
    public function __construct()
    {
        $this->icon = apply_filters('woocommerce_twint_gateway_regular_icon', '');
        $this->has_fields = false;
        $this->supports = array(
            'refunds',
            'products',
        );

        $this->method_title = __('TWINT Checkout', 'woocommerce-gateway-twint');
        $this->title = __('TWINT', 'woocommerce-gateway-twint');
        $this->method_description = __('Allows TWINT Checkout', 'woocommerce-gateway-twint');

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables.
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');

        // Actions.
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        add_filter('woocommerce_payment_complete_order_status', [$this, 'setCompleteOrderStatus'], 10, 3);
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
     * Process the payment and return the result.
     *
     * @param int $order_id
     * @return array
     * @throws Exception
     */
    public function process_payment($order_id): array
    {
        $order = wc_get_order($order_id);
        try {
            $currency = get_woocommerce_currency();
            if (!in_array($currency, static::$amount_supported)) {
                return [
                    'result' => 'error',
                ];
            }

            $order->payment_complete();

            /**
             * Need to reduce stock levels from Order
             */
            wc_reduce_stock_levels($order_id);

            // Remove cart
            // TODO Think about this cart
//            WC()->cart->empty_cart();

            $pairing = (new PairingService())->findByWooOrderId($order_id);
            $qrcode = (new QRCode())->render($pairing->getToken());

            return [
                'result' => 'success',
                'redirect' => false,
                'thankyouUrl' => $this->get_return_url($order),
                'pairingId' => $pairing->getId(),
                'pairingToken' => $pairing->getToken(),
                'currency' => $order->get_currency(),
                'nonce' => wp_create_nonce('twint_check_pairing_status'),
                'qrcode' => $qrcode,
                'shopName' => get_bloginfo('name'),
                'amount' => number_format(
                    $order->get_total(),
                    get_option('woocommerce_price_num_decimals'),
                    get_option('woocommerce_price_decimal_sep'),
                    get_option('woocommerce_price_thousand_sep')
                ),
            ];
        } catch (\Exception $exception) {
            wc_get_logger()->error("Error when processing the payment for order " . PHP_EOL . $exception->getMessage(), [
                'orderID' => $order->get_id(),
                'paymentMethod' => $order->get_payment_method(),
            ]);
        }

        return [];
    }

    /**
     * Set up the status initial for the order first created.
     * @param $status
     * @param $orderId
     * @param $order
     * @return string
     * @since 1.0.0
     *
     */
    public function setCompleteOrderStatus($status, $orderId, $order): string
    {
        if ($order && 'twint_regular' === $order->get_payment_method()) {
            $status = 'pending';
        }

        return $status;
    }

    public static function getOrderStatusAfterCancelled()
    {
        return apply_filters('woocommerce_twint_order_status_cancelled', 'cancelled');
    }

    /**
     * Set up the status of the order after order got paid.
     * @return string
     * @since 1.0.0
     *
     */
    public static function getOrderStatusAfterPaid(): string
    {
        return apply_filters('woocommerce_twint_order_status_paid', 'processing');
    }

    /**
     * Set up the status of the order after order got paid.
     * @return string
     * @since 1.0.0
     *
     */
    public static function getOrderStatusAfterPartiallyRefunded(): string
    {
        return apply_filters('woocommerce_twint_order_status_after_partially_refunded', 'wc-refunded-partial');
    }

    /**
     * Process refund.
     *
     * If the gateway declares 'refunds' support, this will allow it to refund.
     * a passed in amount.
     *
     * @param int $order_id Order ID.
     * @param float|null $amount Refund amount.
     * @param string $reason Refund reason.
     * @return bool|WP_Error True or false based on success, or a WP_Error object.
     * @throws Throwable
     */
    public function process_refund($order_id, $amount = null, $reason = ''): bool|\WP_Error
    {
        $order = wc_get_order($order_id);

        if (!$this->can_refund_order($order)) {
            return new WP_Error('error', __('Refund failed.', 'woocommerce-gateway-twint'));
        }

        // Schedule a delayed status change to "custom-one"
        add_action('woocommerce_order_refunded', function () use ($order) {
            $remainingAmountRefunded = $order->get_remaining_refund_amount();
            if ($remainingAmountRefunded > 0) {
                return $order->update_status('wc-refunded-partial');
            }

            return $order->update_status('wc-refunded');
        }, 10, 1);

        return true;
    }
}
