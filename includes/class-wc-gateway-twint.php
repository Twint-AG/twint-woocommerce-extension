<?php
/**
 * WC_Gateway_TWINT class
 *
 * @author   NFQ Group <tuan.nguyenminh@nfq.com>
 * @package  WooCommerce TWINT Payment Gateway
 * @since    0.0.1
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * TWINT WC_Gateway_TWINT.
 *
 * @class    WC_Gateway_TWINT
 * @version  1.0.7
 */
class WC_Gateway_TWINT extends WC_Payment_Gateway
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
    public $id = 'twint';

    /**
     * Constructor for the gateway.
     */
    public function __construct()
    {
        $this->icon = apply_filters('woocommerce_twint_gateway_icon', '');
        $this->has_fields = false;
        $this->supports = array(
            'pre-orders',
            'products',
            'subscriptions',
            'subscription_cancellation',
            'subscription_suspension',
            'subscription_reactivation',
            'subscription_amount_changes',
            'subscription_date_changes',
            'multiple_subscriptions'
        );

        $this->method_title = _x('TWINT Payment', 'TWINT payment method', 'woocommerce-gateway-twint');
        $this->method_description = __('Allows TWINT payment.', 'woocommerce-gateway-twint');

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables.
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->instructions = $this->get_option('instructions', $this->description);

        // Actions.
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
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
                'label' => __('Enable TWINT Payment', 'woocommerce-gateway-twint'),
                'default' => 'yes',
            ],
            'title' => [
                'title' => __('Title', 'woocommerce-gateway-twint'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'woocommerce-gateway-twint'),
                'default' => _x('TWINT Payment', 'TWINT payment method', 'woocommerce-gateway-twint'),
                'desc_tip' => true,
            ],
            'description' => [
                'title' => __('Description', 'woocommerce-gateway-twint'),
                'type' => 'textarea',
                'description' => __('Payment method description that the customer will see on your checkout.', 'woocommerce-gateway-twint'),
                'default' => __('TWINT Woocommerce Payment Method is a secure and user-friendly plugin that allows Swiss online merchants to accept payments via TWINT, a popular mobile payment solution in Switzerland.', 'woocommerce-gateway-twint'),
                'desc_tip' => true,
            ],
            'testmode' => [
                'title' => 'Test mode',
                'label' => 'Enable Test Mode',
                'type' => 'checkbox',
                'description' => 'Place the payment gateway in test mode using Sandbox/Test mode.',
                'default' => 'yes',
                'desc_tip' => true,
            ],
            'result' => [
                'title' => __('Payment result', 'woocommerce-gateway-twint'),
                'desc' => __('Determine if order payments are successful when using this gateway.', 'woocommerce-gateway-twint'),
                'id' => 'woo_twint_payment_result',
                'type' => 'select',
                'options' => array(
                    'success' => __('Success', 'woocommerce-gateway-twint'),
                    'failure' => __('Failure', 'woocommerce-gateway-twint'),
                ),
                'default' => 'success',
                'desc_tip' => true,
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
            $message = __('Order payment failed. To make a successful payment using TWINT Payment, please review the gateway settings.', 'woocommerce-gateway-twint');
            $order->update_status('failed', $message);
            throw new Exception($message);
        }
    }
}
