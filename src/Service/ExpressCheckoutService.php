<?php

declare(strict_types=1);

namespace Twint\Woo\Service;

use AllowDynamicProperties;
use Exception;
use Throwable;
use Twint\Woo\Model\Gateway\ExpressCheckoutGateway;
use Twint\Woo\Model\Pairing;
use WC_Cart;
use WC_Checkout;
use WC_Data_Exception;
use WC_Order;
use WP_REST_Request;

#[AllowDynamicProperties]
class ExpressCheckoutService
{
    use CartTrait;

    public static array $packages = [];

    public function __construct()
    {
        $this->getCartController();
    }

    /**
     * @throws WC_Data_Exception
     * @throws Exception|Throwable
     */
    public function checkout(bool $wholeCart): Pairing
    {
        // Get the current user
        $customerId = get_current_user_id();

        // Prepare order data
        $order_data = [
            'customer_id' => $customerId,
            'payment_method' => ExpressCheckoutGateway::UNIQUE_PAYMENT_ID,
            'payment_method_title' => __('TWINT Express Checkout', 'twint-woocommerce-extension'),
            'status' => 'checkout-draft',
            'currency' => 'CHF',
            'billing' => [
                'first_name' => 'First',
                'last_name' => 'Last',
                'email' => 'email@example.com',
                'phone' => '1234567890',
                'address_1' => '123 Main St',
                'city' => 'City',
                'state' => '',
                'postcode' => '',
                'country' => 'CH',
            ],
        ];

        // Copy billing to shipping
        $order_data['shipping'] = $order_data['billing'];

        // Create order using WC_Checkout
        $checkout = new WC_Checkout();
        $order_id = $checkout->create_order($order_data);
        $order = wc_get_order($order_id);

        // Add custom meta data
        $order->update_meta_data('_wc_order_attribution_source_type', 'typein');
        $order->update_meta_data('_wc_order_attribution_utm_source', '(direct)');

        // Save the order with meta data
        $order->save();

        // Store shipping packages for later use
        self::$packages = $this->controller->get_shipping_packages();

        if (!$wholeCart) {
            WC()->cart->empty_cart();
        }

        return $this->handlePayment($order);
    }

    /**
     * @throws Exception|Throwable
     */
    private function handlePayment(WC_Order $order): Pairing
    {
        // @phpstan-ignore-next-line
        $gateways = WC()->payment_gateways->payment_gateways();

        /** @var ExpressCheckoutGateway $gatewayInstance */
        $gatewayInstance = $gateways[ExpressCheckoutGateway::UNIQUE_PAYMENT_ID];
        if (!$gatewayInstance) {
            throw new Exception('Payment gateway is not available');
        }

        list($pairing) = $gatewayInstance->process_payment($order->get_id());

        return $pairing;
    }

    public function isEmptyCart(): bool
    {
        $quantity = $this->getCart()->get_cart_item_quantities();

        return count($quantity) === 0;
    }

    private function getCart(): WC_Cart
    {
        return WC()->cart;
    }

    public function addToCart(WP_REST_Request $request): array
    {
        try {
            $this->getCart()->add_to_cart(
                $request['id'],
                $request['quantity'],
                $request['variation_id'],
                $request['variation']
            );

            return [
                'success' => true,
            ];
        } catch (Exception $exception) {
            return [
                'success' => false,
                'message' => $exception->getMessage(),
            ];
        }
    }
}
