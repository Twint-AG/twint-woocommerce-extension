<?php

namespace TWINT\Services;

class SettingService
{
    /**
     * @return bool
     */
    public function isTestMode(): bool
    {
        $settings = get_option('woocommerce_twint_settings');
        return $settings['testmode'] === 'yes';
    }

    /**
     * @return string|null
     */
    public function getMerchantId(): ?string
    {
        return get_option('plugin_twint_settings_merchant_id', null);
    }

    /**
     * @return array
     */
    public function getCertificate(): array
    {
        return get_option('plugin_twint_settings_certificate', []);
    }
}