<?php
/**
 * WC_Gateway_Twint class
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
 * Twint WC_Gateway_Twint.
 *
 * @class    WC_Gateway_Twint
 * @version  1.0.7
 */
class WC_Gateway_Twint extends WC_Payment_Gateway
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

        $this->method_title = _x('Twint Payment', 'Twint payment method', 'woocommerce-gateway-twint');
        $this->method_description = __('Allows Twint payment.', 'woocommerce-gateway-twint');

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
                'label' => __('Enable Twint Payment', 'woocommerce-gateway-twint'),
                'default' => 'yes',
            ],
            'title' => [
                'title' => __('Title', 'woocommerce-gateway-twint'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'woocommerce-gateway-twint'),
                'default' => _x('Twint Payment', 'Twint payment method', 'woocommerce-gateway-twint'),
                'desc_tip' => true,
            ],
            'description' => [
                'title' => __('Description', 'woocommerce-gateway-twint'),
                'type' => 'textarea',
                'description' => __('Payment method description that the customer will see on your checkout.', 'woocommerce-gateway-twint'),
                'default' => __('Twint Woocommerce Payment Method is a secure and user-friendly plugin that allows Swiss online merchants to accept payments via Twint, a popular mobile payment solution in Switzerland.', 'woocommerce-gateway-twint'),
                'desc_tip' => true,
            ],
            'testmode' => [
                'title' => 'Test mode',
                'label' => 'Enable Test Mode',
                'type' => 'checkbox',
                'description' => 'Turning on test mode will use the Twint PAT environment. PAT stands for Production Acceptance Test and allows you to test your Twint integration on an environment closeley resembling the production environment without actually charging a Twint account.',
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
            $message = __('Order payment failed. To make a successful payment using Twint Payment, please review the gateway settings.', 'woocommerce-gateway-twint');
            $order->update_status('failed', $message);
            throw new Exception($message);
        }
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
    public function process_refund($order_id, $amount = null, $reason = '')
    {
        $order = wc_get_order($order_id);

        if (!$this->can_refund_order($order)) {
            return new WP_Error('error', __('Refund failed.', 'woocommerce'));
        }

        // TODO Implement refund feature
    }
}
