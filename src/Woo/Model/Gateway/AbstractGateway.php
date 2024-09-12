<?php

namespace Twint\Woo\Model\Gateway;

use Throwable;
use Twint\TwintPayment;
use WC_Logger_Interface;
use WC_Payment_Gateway;
use WP_Error;

abstract class AbstractGateway extends WC_Payment_Gateway
{
    const UNIQUE_PAYMENT_ID = 'twint_method';

    const SUPPORTED_CURRENCY = 'CHF';

    protected WC_Logger_Interface $logger;

    /**
     * Payment gateway instructions.
     * @var string
     */
    protected string $instructions;

    public function __construct()
    {
        $this->logger = TwintPayment::c('logger');
    }

    public static function getId(): string
    {
        return static::UNIQUE_PAYMENT_ID;
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
        if ($order && static::UNIQUE_PAYMENT_ID === $order->get_payment_method()) {
            $status = 'pending';
        }

        return $status;
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
    public function process_refund($order_id, $amount = null, $reason = ''): bool|WP_Error
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
