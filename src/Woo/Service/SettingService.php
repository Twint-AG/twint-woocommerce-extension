<?php

declare(strict_types=1);

namespace Twint\Woo\Service;

class SettingService
{
    public const KEY_PRIMARY_SETTING = 'woocommerce_twint_regular_settings';

    public const STORE_UUID = 'plugin_twint_settings_store_uuid';

    public const CERTIFICATE = 'plugin_twint_settings_certificate';

    public const CERTIFICATE_PASSWORD = 'plugin_twint_settings_certificate_password';

    public const MINUTES_PENDING_WAIT = 'only_pick_order_from_minutes';

    public const REMOVE_DB_TABLE_WHEN_DISABLING_PLUGIN = '_twint_auto_remove_db_table_when_disabling';

    public const FLAG_VALIDATED_CREDENTIAL_CONFIG = 'plugin_twint_credential_settings_flag_validated';

    public const TEST_MODE = 'plugin_twint_test_mode';

    public const YES = 'yes';

    public const NO = 'no';

    public const PLATFORM = 'WooCommerce';

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
}
