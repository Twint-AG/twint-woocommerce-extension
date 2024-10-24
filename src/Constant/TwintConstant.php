<?php

declare(strict_types=1);

namespace Twint\Woo\Constant;

use Twint\Sdk\Value\InstallSource;

class TwintConstant
{
    public const PLUGIN_VERSION = '9.9.9-dev';

    public const INSTALL_SOURCE = InstallSource::DIRECT;

    public const SUPPORTED_CURRENCY = 'CHF';

    public const CONFIG_CLI_SUPPORT_OPTION = 'woocommerce_twint_cli_support';

    public const CONFIG_EXPRESS_SCREENS = 'twint_express_checkout_display_options';

    public const CONFIG_EXPRESS_ORG_SCREENS = 'woocommerce_twint_express_settings';

    public const KEY_PRIMARY_SETTING = 'woocommerce_twint_regular_settings';

    public const STORE_UUID = 'plugin_twint_settings_store_uuid';

    public const CERTIFICATE = 'plugin_twint_settings_certificate';

    public const CERTIFICATE_PASSWORD = 'plugin_twint_settings_certificate_password';

    public const REMOVE_DB_TABLE_WHEN_DISABLING_PLUGIN = '_twint_auto_remove_db_table_when_disabling';

    public const FLAG_VALIDATED_CREDENTIAL_CONFIG = 'plugin_twint_credential_settings_flag_validated';

    public const TEST_MODE = 'plugin_twint_test_mode';

    public const YES = 'yes';

    public const NO = 'no';

    public const CONFIG_SCREEN_PDP = 'PDP';

    public const CONFIG_SCREEN_PLP = 'PLP';

    public const CONFIG_SCREEN_CART = 'CART';

    public const CONFIG_SCREEN_CART_FLYOUT = 'CART_FLYOUT';

    public const PAIRING_TIMEOUT_REGULAR = 60 * 3; //10 minutes

    public const PAIRING_TIMEOUT_EXPRESS = 60 * 10; //10 minutes

    public const EXCEPTION_VERSION_CONFLICT = 'Version conflict detected. Update aborted.';

    public static function installSource(): InstallSource
    {
        return new InstallSource(self::INSTALL_SOURCE);
    }
}
