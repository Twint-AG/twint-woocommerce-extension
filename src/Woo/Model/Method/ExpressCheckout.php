<?php

declare(strict_types=1);

namespace Twint\Woo\Model\Method;

use Twint\Woo\Model\Gateway\ExpressCheckoutGateway;

/**
 * Twint Payment Blocks integration
 *
 * @since 1.0.0
 */
final class ExpressCheckout extends AbstractMethod
{
    /**
     * Payment method name/id/slug.
     *
     * @var string
     */
    protected $name = ExpressCheckoutGateway::UNIQUE_PAYMENT_ID;
}
