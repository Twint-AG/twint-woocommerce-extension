<?php

declare(strict_types=1);

namespace Twint\Woo\Service;

use Twint\Woo\Constant\TwintConstant;
use WC_Blocks_Utils;

class SettingService
{
    public const KEY_PRIMARY_SETTING = 'woocommerce_twint_regular_settings';

    public const STORE_UUID = 'plugin_twint_settings_store_uuid';

    public const CERTIFICATE = 'plugin_twint_settings_certificate';

    public const CERTIFICATE_PASSWORD = 'plugin_twint_settings_certificate_password';

    public const REMOVE_DB_TABLE_WHEN_DISABLING_PLUGIN = '_twint_auto_remove_db_table_when_disabling';

    public const FLAG_VALIDATED_CREDENTIAL_CONFIG = 'plugin_twint_credential_settings_flag_validated';

    public const TEST_MODE = 'plugin_twint_test_mode';

    public const YES = 'yes';

    public const NO = 'no';

    public static function getAutoRemoveDBTableWhenDisabling()
    {
        return get_option(self::REMOVE_DB_TABLE_WHEN_DISABLING_PLUGIN, self::NO);
    }

    public function isTestMode(): bool
    {
        return get_option(self::TEST_MODE) === self::YES;
    }

    public function getStoreUuid(): ?string
    {
        return get_option(self::STORE_UUID, null);
    }

    public function getCertificate(): ?array
    {
        return get_option(self::CERTIFICATE, null);
    }

    public function isValidated(): bool
    {
        return get_option(self::FLAG_VALIDATED_CREDENTIAL_CONFIG) === self::YES;
    }

    public function getScreens(): array
    {
        return get_option(TwintConstant::CONFIG_EXPRESS_SCREENS);
    }

    /**
     * Detect Version of WooCommerce
     * Return true if Woo/Checkout/Cart using Block.
     * Otherwise, return false
     */
    public function isWooUsingBlockVersion(): bool
    {
        return WC_Blocks_Utils::has_block_in_page(wc_get_page_id('checkout'), 'woocommerce/checkout');
    }
}
