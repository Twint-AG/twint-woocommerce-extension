<?php
/**
 * WC_Gateway_Twint_Express_Checkout class
 *
 * @author   NFQ Group <tuan.nguyenminh@nfq.com>
 * @package  WooCommerce Twint Payment Gateway
 * @since    0.0.1
 */

// Exit if accessed directly.
use Twint\Woo\Services\SettingService;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Twint WC_Gateway_Twint_Express_Checkout.
 *
 * @class WC_Gateway_Twint_Express_Checkout
 * @version  0.0.1
 */
class WC_Gateway_Twint_Express_Checkout extends WC_Payment_Gateway
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
    public $id = 'twint_express';

    /**
     * Button Express checkout label
     * @var string
     */
    public $express_checkout_btn;

    /**
     * Determine the places to display the Express Checkout Button
     * @var array
     */
    public array $display_options;

    /**
     * Constructor for the gateway.
     */
    public function __construct()
    {
        $this->icon = apply_filters('woocommerce_twint_gateway_express_icon', '');
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

        $this->method_title = __('TWINT - Express Checkout', 'woocommerce-gateway-twint');
        $this->method_description = __('Allows TWINT - Express Checkout', 'woocommerce-gateway-twint');

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables.
        $this->description = $this->get_option('description');
        $this->instructions = $this->get_option('instructions', $this->description);

        // Express Checkout Button label
        $this->express_checkout_btn = get_option('twint_express_checkout_label', 'TWINT Express Checkout');

        // Display Options
        $this->display_options = get_option('twint_express_checkout_display_options', []);

        // Actions.
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'saveExpressCheckoutButtonLabelAndDisplayOptions'));

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
                'label' => __('Enable TWINT Express Checkout', 'woocommerce-gateway-twint'),
                'default' => SettingService::YES,
            ],
            'display_options' => [
                'type' => 'display_options',
            ],
            'button_express_checkout' => [
                'type' => 'button_express_checkout',
            ],
        ];
    }

    public function generate_display_options_html(): string
    {
        ob_start();

        $options = [
            'cart' => __('Cart', 'woocommerce-gateway-twint'),
            'mini-cart' => __('Mini Cart', 'woocommerce-gateway-twint'),
            'product-details-page' => __('Product Details Page', 'woocommerce-gateway-twint'),
            'single-product' => __('Single Product', 'woocommerce-gateway-twint'),
        ];

        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label>
                    <?php echo __('Display Screens', 'woocommerce-gateway-twint'); ?>
                </label>
            </th>
            <td class="forminp" id="display_options">
                <div class="wc_input_table_wrapper">
                    <select name="display_options[]" multiple id="display_options" class="select2">
                        <?php foreach ($options as $key => $option) { ?>
                            <option value="<?php echo $key; ?>"
                                <?php echo in_array($key, $this->display_options) ? 'selected' : '' ?>>
                                <?php echo $option; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <script type="text/javascript">
                    jQuery(function () {
                        jQuery('select.select2').select2();
                    });
                </script>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    public function generate_button_express_checkout_html(): string
    {
        ob_start();

        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label><?php echo __('Button config', 'woocommerce-gateway-twint'); ?></label>
            </th>
            <td class="forminp" id="button_express_checkout_label">
                <div class="wc_input_table_wrapper">
                    <input type="text" name="twint_button_label" value="<?php echo $this->express_checkout_btn; ?>">
                    <div class="preview-btn" style="margin-top: 15px; font-size: 14px; font-weight: bold">
                        <?php echo __('Button Preview', 'woocommerce-gateway-twint') ?>
                    </div>
                    <a href="javascript:void(0)" class="twint-button">
                        <span class="twint-button_icon_block">
                            <img class="twint-button_icon"
                                 src="<?php echo twint_assets('/images/express.svg') ?>">
                        </span>
                        <span class="twint-button_label"><?php echo $this->express_checkout_btn; ?></span>
                    </a>
                </div>
                <script type="text/javascript">
                    jQuery(function () {
                        jQuery('input[name="twint_button_label"]').on('change input', function () {
                            const $this = jQuery(this);
                            const $btn = jQuery('a.twint-button');
                            const btnLabel = $this.val();

                            $btn.find('span.twint-button_label').text(btnLabel);

                            return false;
                        });
                    });
                </script>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    /**
     * Handle store custom config fields
     *
     * @return void
     */
    public function saveExpressCheckoutButtonLabelAndDisplayOptions(): void
    {
        $label = $_POST['twint_button_label'] ?? 'TWINT Express Checkout';
        $displayOptions = $_POST['display_options'] ?? [];

        update_option('twint_express_checkout_display_options', $displayOptions);
        update_option('twint_express_checkout_label', $label);
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
            $status = 'pending';
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
