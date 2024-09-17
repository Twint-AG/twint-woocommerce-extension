<?php

declare(strict_types=1);

namespace Twint\Woo\Model\Button;

use Twint\Plugin;
use Twint\Woo\Constant\TwintConstant;
use Twint\Woo\Model\Gateway\ExpressCheckoutGateway;
use Twint\Woo\Model\Modal\Modal;
use Twint\Woo\Model\Modal\Spinner;
use Twint\Woo\Service\SettingService;

class ExpressButton
{
    public function __construct(
        private readonly SettingService $setting,
        private readonly Modal          $modal,
        private readonly Spinner        $spinner,
    )
    {
        if (!is_admin()) {
            add_action('wp', [$this, 'registerHooks']);
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
        $gateways = WC()
            ->payment_gateways()
            ->get_available_payment_gateways();

        return isset($gateways[ExpressCheckoutGateway::getId()]);
    }

    public function registerHooks(): void
    {
        $screens = $this->getAvailableScreens();

        if ($screens !== []) {
            // render spinner
            $this->spinner->registerHooks();
            $this->modal->registerHooks();

            Plugin::enqueueScript('frontend-express', '/express.js');
//            Plugin::enqueueScripts('checkout-express', '/express-checkout.js');
        }

        foreach ($screens as $screen) {
            switch ($screen) {
                case TwintConstant::CONFIG_SCREEN_PDP:
                    add_action('woocommerce_after_add_to_cart_button', [$this, 'renderButton'], 20);
                    break;

                case TwintConstant::CONFIG_SCREEN_PLP:
                    add_filter('woocommerce_loop_add_to_cart_link', [$this, 'renderInProductBox']);

                    break;

                case TwintConstant::CONFIG_SCREEN_CART:
                    add_filter('render_block_woocommerce/cart-express-payment-block', [$this, 'renderExpressButtonInCartPage']);
                    break;
            }
        }
    }

    public function renderExpressButtonInCartPage(string $html): string
    {
        $html .= $this->getButton();

        $html .= $this->renderOrSection();

        return $html;
    }

    public function renderOrSection(): string
    {
        return '
            <div class="wc-block-components-express-payment-continue-rule wc-block-components-express-payment-continue-rule--cart">
               ' . __('Or', 'woocommerce-gateway-twint') . '
            </div> 
        ';
    }

    private function getButton(): string
    {
        return '
            <button type="submit" class="twint-button express">
                <span class="icon-block">
                    <img class="twint-icon" src="' . Plugin::assets('/images/express.svg') . '" alt="Express Checkout">
                </span>
                <span class="twint-label">Express Checkout</span>
            </button>
        ';
    }

    public function renderButton(): void
    {
        echo $this->getButton();
    }

    public function renderInProductBox(string $html): string
    {
        $button = $this->getButton();

        return str_replace('</button>', "</button> {$button}", $html);
    }
}
