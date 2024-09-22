<?php

declare(strict_types=1);

namespace Twint\Woo\Service\Express;

use Twint\Woo\Model\Pairing;

class ExpressOrderService
{
    public function update(Pairing $pairing): void
    {
        $order = wc_get_order($pairing->getWcOrderId());

        $order->payment_complete();
    }
}
