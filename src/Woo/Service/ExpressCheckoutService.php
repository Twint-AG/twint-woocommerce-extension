<?php

declare(strict_types=1);

namespace Twint\Woo\Service;

use AllowDynamicProperties;
use Automattic\WooCommerce\Blocks\StoreApi\Utilities\CartController;
use Exception;
use Throwable;
use Twint\Woo\Model\Gateway\ExpressCheckoutGateway;
use Twint\Woo\Model\Pairing;
use WC_Cart;
use WC_Data_Exception;
use WC_Order;
use WP_REST_Request;

#[AllowDynamicProperties]
class ExpressCheckoutService
{
    /**
     * @var CartController|\Automattic\WooCommerce\StoreApi\Utilities\CartController|mixed
     */
    private mixed $controller;

    public function __construct()
    {
        $legacyController = 'Automattic\WooCommerce\StoreApi\Utilities\CartController';
        $cartController = 'Automattic\WooCommerce\Blocks\StoreApi\Utilities\CartController';
        if (class_exists($legacyController)) {
            $this->controller = new $legacyController();
        } elseif (class_exists($cartController)) {
            $this->controller = new $cartController();
        }
    }

    /**
     * @throws WC_Data_Exception
     * @throws Exception|Throwable
     */
    public function checkout(bool $wholeCart): Pairing
    {
        // Get the current user
        $customerId = get_current_user_id();

        // Create a new order
        $order = wc_create_order([
            'customer_id' => $customerId,
        ]);

        $order->set_currency('CHF');

        $cart = WC()->cart->get_cart();

        foreach ($cart as $item) {
            // Get the product
            $product = $item['data'];
            $quantity = $item['quantity'];

            // Add the product to the order
            $order->add_product($product, $quantity);
        }

        // clear cart when trying EC for single product
        if (!$wholeCart) {
            WC()?->cart?->empty_cart();
        }

        if (!empty($coupons = WC()->cart->get_coupons())) {
            foreach ($coupons as $code => $coupon) {
                $order->add_coupon($code);
            }
        }

        // Calculate totals
        $order->calculate_totals();

        // Add billing address
        $address = [
            'first_name' => 'First',
            'last_name' => 'Last',
            'email' => 'email@example.com',
            'phone' => '1234567890',
            'address_1' => '123 Main St',
            'city' => 'City',
            'state' => 'ST',
            'postcode' => '10001',
            'country' => 'CH',
        ];
        $order->set_address($address, 'billing');
        $order->set_address($address, 'shipping');

        $order->set_payment_method(ExpressCheckoutGateway::UNIQUE_PAYMENT_ID);

        // Set order status
        $order->set_status('checkout-draft');

        // Save the order
        $order->save();

        return $this->handlePayment($order);
    }

    /**
     * @throws Exception|Throwable
     */
    private function handlePayment(WC_Order $order): Pairing
    {
        $gateways = WC()->payment_gateways->payment_gateways();

        /** @var ExpressCheckoutGateway $gatewayInstance */
        $gatewayInstance = $gateways[ExpressCheckoutGateway::UNIQUE_PAYMENT_ID];
        if (!$gatewayInstance) {
            throw new Exception('Payment gateway is not available');
        }

        return $gatewayInstance->process_payment($order->get_id());
    }

    public function isEmptyCart(): bool
    {
        $quantity = $this->getCart()->get_cart_item_quantities();

        return count($quantity) === 0;
    }

    private function getCart(): WC_Cart
    {
        return WC()->cart ?? new WC_Cart();
    }

    public function addToCart(WP_REST_Request $request): void
    {
        $item = apply_filters(
            'woocommerce_store_api_add_to_cart_data',
            [
                'id' => $request['id'],
                'quantity' => $request['quantity'],
                'variation' => $request['variation'],
            ],
            $request
        );

        $this->controller->add_to_cart($item);
    }
}
