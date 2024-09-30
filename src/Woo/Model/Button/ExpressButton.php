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
    ) {
        add_action('wp', [$this, 'registerHooks']);
    }

    public function addExpressCheckoutButtonProductListing(): void
    {
        echo $this->getButton('PLP');
    }

    public function registerHooks(): void
    {
        if (is_admin()) {
            return;
        }

        $screens = $this->getAvailableScreens();

        if ($screens !== []) {
            // render spinner
            $this->spinner->registerHooks();
            $this->modal->registerHooks();

            Plugin::enqueueScript('frontend-express', '/express.js');
        }

        foreach ($screens as $screen) {
            switch ($screen) {
                case TwintConstant::CONFIG_SCREEN_PDP:
                    /**
                     * This Hook is used for both Blocks and Non-Blocks supported.
                     */
                    add_action('woocommerce_after_add_to_cart_button', [$this, 'renderButton'], 20);
                    break;

                case TwintConstant::CONFIG_SCREEN_PLP:
                    add_action('woocommerce_after_shop_loop_item', [$this, 'addExpressCheckoutButtonProductListing']);
                    break;

                case TwintConstant::CONFIG_SCREEN_CART:
                    add_filter(
                        'render_block_woocommerce/cart-express-payment-block',
                        [$this, 'renderExpressButtonInCartPage']
                    );

                    add_action(
                        'woocommerce_proceed_to_checkout',
                        [$this, 'renderExpressButtonInCartPageOldestVersion'],
                        20
                    );
                    break;

                case TwintConstant::CONFIG_SCREEN_CART_FLYOUT:
                    add_filter(
                        'render_block_woocommerce/mini-cart-checkout-button-block',
                        [$this, 'renderButtonInMiniCart']
                    );
                    break;
            }
        }
    }

    public function renderExpressButtonInCartPageOldestVersion(): void
    {
        $html = $this->getButton('cart');

        echo $this->renderOrSection() . $html;
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

    public function renderExpressButtonInCartPage(string $html): string
    {
        $html .= $this->getButton('cart');

        return $html . $this->renderOrSection();
    }

    private function getButton(string $additionalClasses = ''): string
    {
        return '
            <button type="submit" class="twint twint-button ' . $additionalClasses . '">
                <span class="twint icon-block">
                    <img class="twint twint-icon" src="' . Plugin::assets(
                '/images/express.svg'
            ) . '" alt="Express Checkout">
                </span>
                <span class="twint twint-label">Express Checkout</span>
            </button>
        ';
    }

    public function renderOrSection(): string
    {
        return '
            <div class="wc-block-components-express-payment-continue-rule wc-block-components-express-payment-continue-rule--cart">
               ' . __('Or', 'woocommerce-gateway-twint') . '
            </div> 
        ';
    }

    public function renderButtonInMiniCart(string $html): string
    {
        return $html . $this->getButton('mini-cart');
    }

    public function renderButton(): void
    {
        echo $this->getButton('PDP');
    }

    public function renderInProductBox(string $html): string
    {
        $button = $this->getButton('PLP');

        return str_replace('</button>', "</button> {$button}", $html);
    }
}
