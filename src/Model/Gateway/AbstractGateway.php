<?php

declare(strict_types=1);

namespace Twint\Woo\Model\Gateway;

use Throwable;
use Twint\Woo\Model\ApiResponse;
use Twint\Woo\Plugin;
use Twint\Woo\Service\PaymentService;
use WC_Payment_Gateway;
use WP_Error;

abstract class AbstractGateway extends WC_Payment_Gateway
{
    public const UNIQUE_PAYMENT_ID = 'twint_method';

    public const SUPPORTED_CURRENCY = 'CHF';

    public $icon;

    public $supports = ['refunds', 'products'];

    public $method_title;

    public $title;

    public $method_description;

    public $description;

    /**
     * @var array<string, array<'default'|'desc_tip'|'description'|'title'|'type', mixed>|array<'default'|'label'|'title'|'type', mixed>>
     */
    public $form_fields;

    public string $instructions;

    protected mixed $logger;

    public function __construct()
    {
        $this->has_fields = false;
        $this->logger = Plugin::di('logger');
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
     * @since 1.0.0
     */
    public static function getOrderStatusAfterPaid(): string
    {
        return apply_filters('woocommerce_twint_order_status_paid', 'processing');
    }

    /**
     * Set up the status of the order after order got paid.
     * @since 1.0.0
     */
    public static function getOrderStatusAfterFirstTimeCreatedOrder(): string
    {
        return apply_filters('woocommerce_twint_order_status_after_first_time_created', 'wc-pending-payment');
    }

    /**
     * Set up the status initial for the order first created.
     * @param mixed $status
     * @param mixed $orderId
     * @param mixed $order
     * @since 1.0.0
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
     * @throws Throwable
     * @return bool|WP_Error True or false based on success, or a WP_Error object.
     */
    public function process_refund($order_id, $amount = null, $reason = ''): bool|WP_Error
    {
        $order = wc_get_order($order_id);

        /** @var PaymentService $service */
        $service = Plugin::di('payment.service', false);
        $res = $service->reverseOrder($order, (float) $amount);

        return $res instanceof ApiResponse && $res->getReturn()->isSuccessful();
    }
}
