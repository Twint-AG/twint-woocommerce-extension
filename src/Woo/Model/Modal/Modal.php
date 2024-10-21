<?php

declare(strict_types=1);

namespace Twint\Woo\Model\Modal;

use Twint\Woo\Plugin;
use Twint\Woo\Service\AppsService;

class Modal
{
    private bool $registered = false;

    private array $links = [];

    private bool $isAndroid = false;

    private bool $isIos = false;

    private bool $isMobile = false;

    public function __construct(
        private readonly AppsService $service
    ) {
    }

    public function registerHooks(): void
    {
        if ($this->registered || is_admin()) {
            return;
        }

        add_action('wp_footer', [$this, 'render'], 98);

        add_action('wp_enqueue_scripts', static function () {
            wp_enqueue_script(
                'woocommerce-gateway-twint-qrcode',
                'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js'
            );
            wp_enqueue_script(
                'woocommerce-gateway-twint-clipboard',
                'https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/1.4.0/clipboard.min.js'
            );
        });
    }

    public function render(): void
    {
        $this->getVariables();

        echo $this->getContent();
    }

    private function getVariables(): void
    {
        $links = $this->service->getPayLinks();

        $this->links = $links;

        $this->isAndroid = isset($links['android']);
        $this->isIos = isset($links['ios']);

        $this->isMobile = $this->isAndroid || $this->isIos;
    }

    private function getContent(): string
    {
        return '
            <div id="twint-modal" class="!hidden"
                data-exist-label="' . __('View cart', 'woocommerce') . '"
                data-exist-message="' . __('You have existing products in the shopping cart. Please review your shopping cart before continue.', 'woocommerce-gateway-twint') . '"
                >
                <div class="fixed inset-0 bg-black opacity-50"></div>
                <div class="modal-inner-wrap shadow-lg w-screen h-screen p-6 z-10 overflow-y-auto ' . $this->getMdClasses(
            'md:rounded-lg md:h-auto md:max-h-[95vh]'
        ) . '">
                    <header class="twint-modal-header sticky top-0 flex justify-between items-center bg-white py-2 px-4 ' . $this->getMdClasses(
            'md:rounded-t-lg'
        ) . '">
                        <button id="twint-close"
                           data-default="' . __('Cancel checkout', 'woocommerce-gateway-twint') . '"
                           data-success="' . __('Continue shopping', 'woocommerce-gateway-twint') . '"
                        >
                            <svg width="14" height="13" viewBox="0 0 14 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1.40001 12.8078L0.692261 12.1L6.29226 6.50001L0.692261 0.900011L1.40001 0.192261L7.00001 5.79226L12.6 0.192261L13.3078 0.900011L7.70776 6.50001L13.3078 12.1L12.6 12.8078L7.00001 7.20776L1.40001 12.8078Z" fill="#1C1B1F"></path>
                            </svg>
                            <span class="ml-2"></span>
                        </button>
                        <img class="twint-logo hidden ' . $this->getMdClasses('md:block') . '" src="' . Plugin::assets(
            '/images/twint_logo.png'
        ) . '" 
                        alt="TWINT Logo">
                    </header>
                    <div class="modal-content twint-modal-content p-0 ' . $this->getMobileClass() . $this->getMdClasses(
            'md:p-4'
        ) . '">
                        <div id="qr-modal-success"></div>
                        <div id="qr-modal-content" class="text-20">
                        <input type="hidden" name="twint_wp_nonce" value={nonce} id="twint_wp_nonce"/>
                        <div class="flex flex-col  gap-4 bg-gray-100 ' . $this->getMdClasses('md:flex-row') . '">
                            <div class="flex flex-1 order-1 bg-white items-center justify-center ' . $this->getMdClasses(
            'md:flex md:order-none md:rounded-lg'
        ) . '">
                                <div class="flex flex-col text-center ' . $this->getMdClasses(
            'md:flex-col-reverse'
        ) . '">
                                    <div class="qr-token text-center my-3">
                                        <input id="qr-token"
                                               class="bg-white"
                                               type="text"
                                               value={pairingToken}
                                               disabled="disabled"
                                        />
                                    </div>

                                    <div class="text-center my-4 ' . $this->getMdClasses('md:hidden') . '">
                                        <button id="twint-copy-btn"
                                            data-clipboard-action="copy"
                                            data-clipboard-target="#qr-token"
                                            data-default="' . __('Copy code', 'woocommerce-gateway-twint') . '"
                                            data-copied="' . __('Copied', 'woocommerce-gateway-twint') . '"
                                            class="p-4 px-6 !bg-white rounded-lg border-black">
                                        </button>
                                    </div>

                                    <div id="qrcode" class="hidden text-center items-center justify-center m-4 
                                    ' . $this->getMdClasses('md:flex') . '"
                                        title={pairingToken}>
                                    </div>
                                </div>
                            </div>

                            <div class="flex-1 order-0 flex flex-col gap-1 ' . $this->getMdClasses(
            'md:gap-4 md:order-1'
        ) . '">
                                <div class="flex flex-1 bg-white p-4 items-center justify-center ' . $this->getMdClasses(
            'md:rounded-lg'
        ) . '">
                                        <span id="twint-amount">
                                            {price}
                                        </span>
                                </div>
                                <div class="flex flex-1 bg-white p-4 items-center justify-center ' . $this->getMdClasses(
            'md:rounded-lg'
        ) . '">
                                    ' . get_bloginfo('name') . '
                                </div>

                                <div class="app-selector ' . $this->getMdClasses('md:hidden') . '">
                                    ' . $this->getAndroidHtml() . '
                                    ' . $this->getIosHtml() . '
                                    <div class="text-center ' . $this->getMdClasses('md:hidden') . '">
                                        <div class="flex items-center justify-center mx-4">
                                            <div
                                                class="flex-grow border-b-0 border-t border-solid border-gray-300"></div>
                                            <span class="mx-4 text-black">
                                                ' . __('or', 'woocommerce-gateway-twint') . '
                                            </span>
                                            <div
                                                class="flex-grow border-b-0 border-t border-solid border-gray-300"></div>
                                        </div>

                                        <div class="row my-3">
                                            <div class="col-9 text-center">
                                                ' . __('Enter this code in your TWINT app:', 'woocommerce-gateway-twint') . '
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="container mx-auto mt-4 text-16 p-4">
                            <div class="grid grid-cols-1 md:grid-cols-2">
                                <div class="hidden flex-col items-center ' . $this->getMdClasses('md:flex') . '">
                                    <div class="flex justify-center">
                                        <img class="w-55 h-55"
                                             src="' . Plugin::assets('/images/icon-scan.svg') . '"
                                             alt="scan"/>
                                    </div>
                                    <div class="text-center mt-4">
                                        ' . __('Scan this QR Code with your TWINT app to complete the checkout.', 'woocommerce-gateway-twint') . '
                                    </div>
                                </div>
                                <div id="twint-guide-contact" class="flex flex-col items-center">
                                    <div class="flex justify-center">
                                        <img class="w-55 h-55" src="' . Plugin::assets(
            '/images/icon-contact.svg'
        ) . '" alt="contact">
                                    </div>
                                    <div class="text-center mt-4">' . __('Follow the instructions in the app to confirm your order.', 'woocommerce-gateway-twint') . '</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
                </div>                
            </div>';
    }

    private function getMdClasses(string $classes): string
    {
        return $this->isMobile ? '' : $classes;
    }

    private function getMobileClass(): string
    {
        return $this->isMobile ? ' twint-mobile ' : '';
    }

    private function getAndroidHtml(): string
    {
        if (!$this->isAndroid) {
            return '';
        }

        $link = $this->links['android'];

        return '
            <div class="text-center mt-4 px-4">
                <a id="twint-addroid-button"
                   data-href="javascript:window.location = \'' . $link . '\'"
                   href="javascript:window.location = \'' . $link . '\'">
                    ' . __('Switch to TWINT app now', 'woocommerce-gateway-twint') . '
                </a>
            </div>
        ';
    }

    private function getIosHtml(): string
    {
        if (!$this->isIos) {
            return '';
        }

        $links = $this->links['ios'];

        $refinedApps = [
            'UBS TWINT' => 'bank-ubs',
            'Raiffeisen TWINT' => 'bank-raiffeisen',
            'PostFinance TWINT' => 'bank-pf',
            'ZKB TWINT' => 'bank-zkb',
            'Credit Suisse TWINT' => 'bank-cs',
            'BCV TWINT' => 'bank-bcv',
        ];

        $app = '';
        $else = '';

        foreach ($links as $link) {
            $icon = $refinedApps[$link['name']] ?? null;
            if ($icon) {
                $app .= '<img src="' . Plugin::assets("/images/{$icon}.png") . '" 
                    class="shadow-2xl w-64 h-64 rounded-2xl mx-auto"
                    data-link="' . htmlentities($link['link']) . '"
                    alt="' . htmlentities($link['name']) . '">';
            } else {
                $else .= '<option value="' . htmlentities($link['link']) . '">' . htmlentities(
                    $link['name']
                ) . '</option>';
            }
        }

        return '
            <div id="twint-ios-container">
                <div class="my-6 text-center">
                    ' . __('Choose your TWINT app:', 'woocommerce-gateway-twint') . '
                </div>
    
                <div class="twint-app-container w-3/4 mx-auto justify-center max-w-screen-md mx-auto grid grid-cols-3 gap-4">
                    ' . $app . '
                </div>
                
                <select class="twint-select">
                    <option>' . __('Other banks', 'woocommerce-gateway-twint') . '</option>
                    ' . $else . '
                </select>    
            </div>        
        ';
    }
}
