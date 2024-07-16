<?php

namespace Twint\Woo\Services;

class SettingService
{
    const KEY_PRIMARY_SETTING = 'woocommerce_twint_regular_settings';
    const MERCHANT_ID = 'plugin_twint_settings_merchant_id';
    const CERTIFICATE = 'plugin_twint_settings_certificate';
    const CERTIFICATE_PASSWORD = 'plugin_twint_settings_certificate_password';
    const EXPRESS_CHECKOUT_SINGLE = 'plugin_twint_express_checkout_single';
    const MINUTES_PENDING_WAIT = 'only_pick_order_from_minutes';
    const REMOVE_DB_TABLE_WHEN_DISABLING_PLUGIN = '_twint_auto_remove_db_table_when_disabling';
    const FLAG_VALIDATED_CREDENTIAL_CONFIG = 'plugin_twint_credential_settings_flag_validated';
    const TESTMODE = 'plugin_twint_test_mode';

    /**
     * @return bool
     */
    public function isTestMode(): bool
    {
        return get_option(self::TESTMODE) === 'yes';
    }

    /**
     * @return string|null
     */
    public function getMerchantId(): ?string
    {
        return get_option(self::MERCHANT_ID, null);
    }

    /**
     * @return ?array
     */
    public function getCertificate(): ?array
    {
        return get_option(self::CERTIFICATE, null);
    }

    public static function getCheckoutSingle()
    {
        return get_option(self::EXPRESS_CHECKOUT_SINGLE);
    }

    public static function getMinutesPendingWait()
    {
        return get_option(self::MINUTES_PENDING_WAIT, 30);
    }

    public static function getAutoRemoveDBTableWhenDisabling()
    {
        return get_option(self::REMOVE_DB_TABLE_WHEN_DISABLING_PLUGIN, 'no');
    }
}