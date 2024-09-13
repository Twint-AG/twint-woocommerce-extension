<?php

declare(strict_types=1);

namespace Twint\Woo\Model\Method;

use Twint\Woo\Model\Gateway\RegularCheckoutGateway;

final class RegularCheckout extends AbstractMethod
{
    /**
     * Payment method name/id/slug.
     *
     * @var string
     */
    protected $name = RegularCheckoutGateway::UNIQUE_PAYMENT_ID;
}
