<?php

namespace Twint\Woo\Service;

class SettingService
{
    const KEY_PRIMARY_SETTING = 'woocommerce_twint_regular_settings';
    const STORE_UUID = 'plugin_twint_settings_store_uuid';
    const CERTIFICATE = 'plugin_twint_settings_certificate';
    const CERTIFICATE_PASSWORD = 'plugin_twint_settings_certificate_password';
    const MINUTES_PENDING_WAIT = 'only_pick_order_from_minutes';
    const REMOVE_DB_TABLE_WHEN_DISABLING_PLUGIN = '_twint_auto_remove_db_table_when_disabling';
    const FLAG_VALIDATED_CREDENTIAL_CONFIG = 'plugin_twint_credential_settings_flag_validated';
    const TEST_MODE = 'plugin_twint_test_mode';
    const YES = 'yes';
    const NO = 'no';
    const PLATFORM = 'WooCommerce';

    public static function getAutoRemoveDBTableWhenDisabling()
    {
        return get_option(self::REMOVE_DB_TABLE_WHEN_DISABLING_PLUGIN, self::NO);
    }

    /**
     * @return bool
     */
    public function isTestMode(): bool
    {
        return get_option(self::TEST_MODE) === self::YES;
    }

    /**
     * @return string|null
     */
    public function getStoreUuid(): ?string
    {
        return get_option(self::STORE_UUID, null);
    }

    /**
     * @return ?array
     */
    public function getCertificate(): ?array
    {
        return get_option(self::CERTIFICATE, null);
    }
}
