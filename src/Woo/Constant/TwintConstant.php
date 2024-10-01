<?php

declare(strict_types=1);

namespace Twint\Woo\Constant;

class TwintConstant
{
    public const PLATFORM = 'WooCommerce';

    public const SIMPLE_PRODUCT = 'simple';

    public const MINUTES_PENDING_WAIT = 'only_pick_order_from_minutes';

    public const SUPPORTED_CURRENCY = 'CHF';

    public const CONFIG_CLI_SUPPORT_OPTION = 'woocommerce_twint_cli_support';

    public const CONFIG_EXPRESS_SCREENS = 'twint_express_checkout_display_options';

    public const CONFIG_SCREEN_PDP = 'PDP';

    public const CONFIG_SCREEN_PLP = 'PLP';

    public const CONFIG_SCREEN_CART = 'CART';

    public const CONFIG_SCREEN_CART_FLYOUT = 'CART_FLYOUT';

    public const PAIRING_TIMEOUT_REGULAR = 60 * 3; //3 mins

    public const PAIRING_TIMEOUT_EXPRESS = 60 * 5; //5 mins

    public const EXCEPTION_VERSION_CONFLICT = 'Version conflict detected. Update aborted.';
}
