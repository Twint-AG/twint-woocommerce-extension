<?php

namespace Twint\Woo\Services;

class SettingService
{
    const KEY_PRIMARY_SETTING = 'woocommerce_twint_settings';
    const MERCHANT_ID = 'plugin_twint_settings_merchant_id';
    const CERTIFICATE = 'plugin_twint_settings_certificate';
    const EXPRESS_CHECKOUT_SINGLE = 'plugin_twint_express_checkout_single';
    const MINUTES_PENDING_WAIT = 'only_pick_order_from_minutes';
    const REMOVE_DB_TABLE_WHEN_DISABLING_PLUGIN = '_twint_auto_remove_db_table_when_disabling';

    /**
     * @return bool
     */
    public function isTestMode(): bool
    {
        $settings = get_option(self::KEY_PRIMARY_SETTING);
        return $settings['testmode'] === 'yes';
    }

    /**
     * @return string|null
     */
    public function getMerchantId(): ?string
    {
        return get_option(self::MERCHANT_ID, null);
    }

    /**
     * @return array
     */
    public function getCertificate(): array
    {
        return get_option(self::CERTIFICATE, []);
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