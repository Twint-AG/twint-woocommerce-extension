<?php

namespace Twint\Woo\Model\Gateway;

use chillerlan\QRCode\QRCode;
use Exception;
use Twint\TwintPayment;
use Twint\Woo\Repository\PairingRepository;
use Twint\Woo\Service\SettingService;


class RegularCheckoutGateway extends AbstractGateway
{
    const UNIQUE_PAYMENT_ID = 'twint_regular';

    public $id = self::UNIQUE_PAYMENT_ID;

    /**
     * Constructor for the gateway.
     */
    public function __construct()
    {
        parent::__construct();

        $this->icon = apply_filters('woocommerce_twint_gateway_regular_icon', '');
        $this->has_fields = false;
        $this->supports = [
            'refunds',
            'products',
        ];

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
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);

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

            if ($currency !== static::SUPPORTED_CURRENCY) {
                return [
                    'result' => "Payment method only support for " . static::SUPPORTED_CURRENCY
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

            $pairing = TwintPayment::c('pairing.repository')->findByWooOrderId($order_id);
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
        } catch (Exception $e) {
            $this->logger->error("Error when processing the payment for order " . PHP_EOL . $e->getMessage(), [
                'orderID' => $order->get_id(),
                'paymentMethod' => $order->get_payment_method(),
            ]);

            throw $e;
        }
    }
}
