<?php

declare(strict_types=1);

namespace Twint\Woo\Model\Gateway;

use Throwable;
use Twint\Woo\Constant\TwintConstant;
use Twint\Woo\Model\ApiResponse;
use Twint\Woo\Plugin;
use Twint\Woo\Service\PaymentService;
use WC_Order;
use WC_Payment_Gateway;
use WP_Error;

abstract class AbstractGateway extends WC_Payment_Gateway
{
    public const UNIQUE_PAYMENT_ID = 'twint_method';

    public const SUPPORTED_CURRENCY = TwintConstant::SUPPORTED_CURRENCY;

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

    public string $instructions = '';

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
     * @param WC_Order $order
     * @since 1.0.0
     */
    public function setCompleteOrderStatus($status, $orderId, $order): string
    {
        if ($order && static::UNIQUE_PAYMENT_ID === $order->get_payment_method() && empty($order->get_transaction_id())) {
            $status = 'pending-payment';
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

    public function is_available(): bool
    {
        if (!parent::is_available()) {
            return false;
        }

        return get_woocommerce_currency() === self::SUPPORTED_CURRENCY;
    }

    public function is_account_connected(): bool
    {
        return get_option(TwintConstant::FLAG_VALIDATED_CREDENTIAL_CONFIG) === TwintConstant::YES;
    }

    public function is_test_mode(): bool
    {
        return get_option(TwintConstant::TEST_MODE) === TwintConstant::YES;
    }

    public function is_dev_mode(): bool
    {
        return false;
    }

    public function is_onboarding_started(): bool
    {
        return true;
    }

    public function is_onboarding_completed(): bool
    {
        return get_option(TwintConstant::FLAG_VALIDATED_CREDENTIAL_CONFIG) === TwintConstant::YES;
    }

    public function is_test_mode_onboarding(): bool
    {
        return get_option(TwintConstant::TEST_MODE) === TwintConstant::YES;
    }

    public function get_settings_url(): string
    {
        return admin_url('admin.php?page=twint-payment-integration-settings');
    }

    public function get_connection_url(): string
    {
        return '';
    }

    public function get_recommended_payment_methods(string $country_code = ''): array
    {
        return [];
    }

    public function get_icon_url()
    {
        return Plugin::assets('/images/twint_logo.png');
    }

    public function get_icon()
    {
        $icon_html = '<img class="" src="twint-logo-icon"' . esc_attr(
            $this->get_icon_url()
        ) . '" alt="Twint Checkout" />';

        return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
    }
}
