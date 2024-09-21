<?php

declare(strict_types=1);

namespace Twint\Woo\Api\Frontend;

trait CartInitTrait
{
    protected function initCartIfNeed(): void
    {
        $woo = WC();

        if (!$woo->cart) {
            $woo->frontend_includes();
            $woo->initialize_session();
            $woo->initialize_cart();
        }
    }
}
