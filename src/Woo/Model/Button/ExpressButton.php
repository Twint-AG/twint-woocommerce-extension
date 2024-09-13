<?php

namespace Twint\Woo\Model\Button;

use Twint\Plugin;
use Twint\Woo\Constant\TwintConstant;
use Twint\Woo\Model\Gateway\ExpressCheckoutGateway;
use Twint\Woo\Service\SettingService;

class ExpressButton
{
    public function __construct(
        private readonly SettingService $setting
    )
    {
        if(!is_admin()) {
            $this->registerHooks();
        }
    }

    protected function getAvailableScreens(): array
    {
        $validated = $this->setting->isValidated();
        $enabled = $this->isPaymentEnabled();
        $currency = get_woocommerce_currency() === TwintConstant::SUPPORTED_CURRENCY;

        return ($validated && $enabled && $currency) ? $this->setting->getScreens() : [];
    }

    private function isPaymentEnabled(): bool
    {
        $gateways = WC()->payment_gateways()->get_available_payment_gateways();

        return isset($gateways[ExpressCheckoutGateway::getId()]);
    }

    protected function registerHooks(): void
    {
        foreach ($this->getAvailableScreens() as $screen){
            switch ($screen){
                case TwintConstant::CONFIG_SCREEN_PDP:
                    add_action('woocommerce_after_add_to_cart_button', [$this, 'renderButton'], 20);
                    break;
            }
        }
    }

    public function renderButton(): void
    {
        echo '
            <button type="submit" class="twint-button express">
                <span class="icon-block">
                    <img class="twint-icon" src="'. Plugin::assets('/images/express.svg') .'" alt="Express Checkout">
                </span>
                <span class="twint-label">Express Checkout</span>
            </button>
        ';
    }
}
