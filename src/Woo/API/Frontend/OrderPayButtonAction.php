<?php

namespace Twint\Woo\API\Frontend;

use Twint\Woo\Model\Gateway\RegularCheckoutGateway;

class OrderPayButtonAction
{
    public function __construct()
    {
        $this->registerHooks();
    }

    public function registerHooks(): void
    {
        /**
         * These 2 (action and filter) for "Pay" for the order in My account > Orders section.
         */
        add_action('woocommerce_view_order', [$this, 'addOrderPayButton']);
        add_filter('woocommerce_valid_order_statuses_for_payment', [$this, 'appendValidStatusForOrderNeedPayment']);
    }

    public function addOrderPayButton($orderId): void
    {
        $order = wc_get_order($orderId);

        if ('wc-' . $order->get_status() === RegularCheckoutGateway::getOrderStatusAfterFirstTimeCreatedOrder()) {
            printf(
                '<a class="woocommerce-button wp-element-button button pay" href="%s">%s</a>',
                $order->get_checkout_payment_url(), __('Pay for this order', 'woocommerce')
            );
        }
    }

    public function appendValidStatusForOrderNeedPayment($statuses)
    {
        $statuses[] = 'twint-pending';
        return $statuses;
    }
}