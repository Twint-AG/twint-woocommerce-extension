<?php

namespace Twint\Woo\Services;

class SettingService
{
    const KEY_PRIMARY_SETTING = 'woocommerce_twint_settings';
    const MERCHANT_ID = 'plugin_twint_settings_merchant_id';
    const CERTIFICATE = 'plugin_twint_settings_certificate';

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
}