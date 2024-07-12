<?php
/**
 * WC_Gateway_Twint_Regular_Checkout class
 *
 * @author   NFQ Group <tuan.nguyenminh@nfq.com>
 * @package  WooCommerce Twint Payment Gateway
 * @since    0.0.1
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Twint WC_Gateway_Twint_Regular_Checkout.
 *
 * @class WC_Gateway_Twint_Regular_Checkout
 * @version  0.0.1
 */
class WC_Gateway_Twint_Regular_Checkout extends WC_Payment_Gateway
{
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
    public $id = 'twint_regular';

    /**
     * Constructor for the gateway.
     */
    public function __construct()
    {
        $this->icon = apply_filters('woocommerce_twint_gateway_regular_icon', '');
        $this->has_fields = false;
        $this->supports = array(
            'pre-orders',
            'refunds',
            'products',
            'subscriptions',
            'subscription_cancellation',
            'subscription_suspension',
            'subscription_reactivation',
            'subscription_amount_changes',
            'subscription_date_changes',
            'multiple_subscriptions'
        );

        $this->method_title = __('TWINT - Regular Checkout | TBU - Twint Payment', 'woocommerce-gateway-twint');
        $this->method_description = __('Allows TWINT - Regular Checkout', 'woocommerce-gateway-twint');

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables.
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->instructions = $this->get_option('instructions', $this->description);

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
                'label' => __('Enable Twint Regular Checkout', 'woocommerce-gateway-twint'),
                'default' => 'yes',
            ],
            'title' => [
                'title' => __('Title', 'woocommerce-gateway-twint'),
                'type' => 'safe_text',
                'description' => __('This controls the title which the user sees during checkout.', 'woocommerce-gateway-twint'),
                'desc_tip' => true,
                'default' => __('TWINT', 'woocommerce-gateway-twint'),
            ],
            'description' => [
                'title' => __('Description', 'woocommerce-gateway-twint'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'woocommerce-gateway-twint'),
                'default' => __('Regular Checkout Payment Plugin supported by TWINT', 'woocommerce-gateway-twint'),
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
        $payment_result = $this->get_option('result');
        $order = wc_get_order($order_id);

        if ('success' === $payment_result) {
            $order->payment_complete();

            /**
             * Need to reduce stock levels from Order
             */
            wc_reduce_stock_levels($order_id);

            // Remove cart
            WC()->cart->empty_cart();

            // Return thankyou redirect
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            );
        } else {
            $message = __('Order payment failed. To make a successful payment using TWINT Regular Checkout payment, please review the gateway settings.', 'woocommerce-gateway-twint');
            $order->update_status('failed', $message);
            throw new Exception($message);
        }
    }

    /**
     * Set up the status initial for the order first created.
     * @param $status
     * @param $orderId
     * @param $order
     * @return string
     * @since 0.0.1
     *
     */
    public function setCompleteOrderStatus($status, $orderId, $order): string
    {
        if ($order && $this->id === $order->get_payment_method()) {
            // TODO use config or database option for this.
            $status = 'wc-pending';
        }

        return $status;
    }

    /**
     * Set up the status of the order after order got paid.
     * @return string
     * @since 0.0.1
     *
     */
    public static function getOrderStatusAfterPaid(): string
    {
        // TODO use config or database option for this.
        return apply_filters('woocommerce_twint_order_status_paid', 'processing');
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
     * @return bool|\WP_Error True or false based on success, or a WP_Error object.
     */
    public function process_refund($order_id, $amount = null, $reason = ''): bool|\WP_Error
    {
        $order = wc_get_order($order_id);

        if (!$this->can_refund_order($order)) {
            return new WP_Error('error', __('Refund failed.', 'woocommerce-gateway-twint'));
        }

        // TODO Implement refund feature
    }
}
