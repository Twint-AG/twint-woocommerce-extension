<?php

declare(strict_types=1);

namespace Twint\Woo\Service;

use Twint\Woo\Constant\TwintConstant;
use WC_Blocks_Utils;

class SettingService
{

    public static function getAutoRemoveDBTableWhenDisabling()
    {
        return get_option(TwintConstant::REMOVE_DB_TABLE_WHEN_DISABLING_PLUGIN, TwintConstant::NO);
    }

    public function isTestMode(): bool
    {
        return get_option(TwintConstant::TEST_MODE) === TwintConstant::YES;
    }

    public function getStoreUuid(): ?string
    {
        return get_option(TwintConstant::STORE_UUID, null);
    }

    public function getCertificate(): ?array
    {
        return get_option(TwintConstant::CERTIFICATE, null);
    }

    public function isValidated(): bool
    {
        return get_option(TwintConstant::FLAG_VALIDATED_CREDENTIAL_CONFIG) === TwintConstant::YES;
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
