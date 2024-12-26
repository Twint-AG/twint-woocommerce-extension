<?php

declare(strict_types=1);

namespace Twint\Woo\Model\Button;

use Twint\Woo\Constant\TwintConstant;
use Twint\Woo\Container\Lazy;
use Twint\Woo\Container\LazyLoadTrait;
use Twint\Woo\Model\Gateway\ExpressCheckoutGateway;
use Twint\Woo\Model\Modal\Modal;
use Twint\Woo\Model\Modal\Spinner;
use Twint\Woo\Plugin;
use Twint\Woo\Service\SettingService;

/**
 * @method Spinner getSpinner()
 * @method Modal getModal()
 * @method SettingService getSetting()
 */
class ExpressButton
{
    use LazyLoadTrait;

    protected static array $lazyLoads = ['setting', 'modal', 'spinner'];

    public function __construct(
        private Lazy|SettingService $setting,
        private Lazy|Modal          $modal,
        private Lazy|Spinner        $spinner,
    ) {
        add_action('wp', [$this, 'registerHooks']);
    }

    public function registerHooks(): void
    {
        if (is_admin()) {
            return;
        }

        add_filter('wp_resource_hints', [$this, 'addReconnectForGoogleFonts'], 10, 2);

        add_filter('body_class', [$this, 'addPluginVersion']);

        $screens = $this->getAvailableScreens();

        if ($screens !== []) {
            // render spinner
            $this->getSpinner()->registerHooks();
            $this->getModal()->registerHooks();

            Plugin::enqueueScript('frontend-express', '/express.js');

            // Google Font
            wp_enqueue_style(
                'google-roboto-font',
                'https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap',
                [],
                TwintConstant::PLUGIN_VERSION
            );
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
                    add_filter('woocommerce_loop_add_to_cart_link', [$this, 'renderInProductBox']);
                    break;

                case TwintConstant::CONFIG_SCREEN_CART:
                    add_filter(
                        'render_block_woocommerce/cart-express-payment-block',
                        [$this, 'renderExpressButtonInCartPage']
                    );

                    add_action('woocommerce_proceed_to_checkout', [$this, 'addToLegacyCartPage'], 1);
                    break;

                case TwintConstant::CONFIG_SCREEN_CART_FLYOUT:
                    add_filter('body_class', [$this, 'addBodyClass']);
                    add_filter(
                        'render_block_woocommerce/mini-cart-checkout-button-block',
                        [$this, 'renderButtonInMiniCart']
                    );
                    add_action('woocommerce_widget_shopping_cart_buttons', [$this, 'addToNonBlockMiniCart'], 30);
                    break;
            }
        }
    }

    protected function getAvailableScreens(): array
    {
        $validated = $this->getSetting()->isValidated();
        $enabled = $this->isPaymentEnabled();
        $currency = get_woocommerce_currency() === TwintConstant::SUPPORTED_CURRENCY;

        return ($validated && $enabled && $currency) ? $this->getSetting()->getScreens() : [];
    }

    private function isPaymentEnabled(): bool
    {
        $gateways = WC()
            ->payment_gateways()
            ->get_available_payment_gateways();

        return isset($gateways[ExpressCheckoutGateway::getId()]);
    }

    public function addReconnectForGoogleFonts($hints, $relation_type): array
    {
        if ($relation_type === 'preconnect') {
            $hints[] = [
                'href' => 'https://fonts.gstatic.com',
                'crossorigin' => '',
            ];

            $hints[] = [
                'href' => 'https://fonts.googleapis.com',
            ];
        }

        return $hints;
    }

    public function addPluginVersion($classes): array
    {
        $classes[] = 'twint-version-' . TwintConstant::PLUGIN_VERSION;

        return $classes;
    }

    public function addBodyClass($classes): array
    {
        $classes[] = 'twint-enabled';

        return $classes;
    }

    public function addToNonBlockMiniCart(): void
    {
        //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The HTML is safe and intended for raw output.
        echo $this->getButton('mini-cart dynamic');
    }

    private function getButton(string $additionalClasses = ''): string
    {
        //phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
        return '
            <button type="submit" class="twint twint-button ' . $additionalClasses . '">
                <span class="twint twint-icon-block">
                    <img class="twint twint-icon" src="' .
            //phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
            Plugin::assets(
                '/images/express.svg'
            ) . '" alt="Express Checkout">
                </span>
                <span class="twint twint-label">Express Checkout</span>
            </button>
        ';
    }

    public function addToLegacyCartPage(): void
    {
        //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The HTML is safe and intended for raw output.
        echo $this->getButton('cart') . $this->renderOrSection();
    }

    public function renderOrSection(): string
    {
        return '
            <div class="wc-block-components-express-payment-continue-rule wc-block-components-express-payment-continue-rule--cart">
               ' . __('Or', 'twint-woocommerce-extension') . '
            </div> 
        ';
    }

    public function renderExpressButtonInCartPage(string $html): string
    {
        $html .= $this->getButton('cart');

        return $html . $this->renderOrSection();
    }

    public function renderButtonInMiniCart(string $html): string
    {
        return $html . $this->getButton('mini-cart dynamic');
    }

    public function renderButton(): void
    {
        //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The HTML is safe and intended for raw output.
        echo $this->getButton('PDP');
    }

    public function renderInProductBox(string $html): string
    {
        $button = $this->getButton('PLP');

        // Attempt to replace within a button element
        $buttonInserted = str_contains($html, '</button>');
        if ($buttonInserted) {
            $html = str_replace('</button>', "</button> {$button}", $html);
        }

        // If no button element is found and it's not a variable product, try replacing within an anchor tag
        // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- need to match on translated value from core.
        $text = __('Add to cart', 'woocommerce');
        if (!$buttonInserted && str_contains($html, $text)) {
            $html = str_replace('</a>', "</a> {$button}", $html);
        }

        return $html;
    }
}
