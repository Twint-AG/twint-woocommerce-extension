<?php

namespace Twint\Woo\Model\Method;

use Twint\Woo\Gateway\RegularCheckoutGateway;

/**
 *
 */
final class RegularCheckout extends AbstractMethod
{
    /**
     * Payment method name/id/slug.
     *
     * @var string
     */
    protected $name = RegularCheckoutGateway::UNIQUE_PAYMENT_ID;
}
