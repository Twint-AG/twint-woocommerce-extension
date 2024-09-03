<?php

namespace Twint\Woo\Templates;

use chillerlan\QRCode\QRCode;
use Twint\Woo\Services\PairingService;
use Twint\Woo\Services\PaymentService;

class QrCodeViewAdapter
{
    /**
     * @var PaymentService
     */
    private PaymentService $paymentService;

    /**
     * @var PairingService
     */
    private PairingService $pairingService;

    /**
     * @var \WC_Order
     */
    private \WC_Order $order;

    public function __construct(\WC_Order $order)
    {
        $this->order = $order;
        $this->paymentService = new PaymentService();
        $this->pairingService = new PairingService();
    }

    public function render(): void
    {
        $pairing = $this->pairingService->findByWooOrderId($this->order->get_id());
        if (empty($pairing)) {
            return;
        }

        $payLinks = $this->paymentService->getPayLinks($pairing->getToken());
        $qrcode = (new QRCode())->render($pairing->getToken());
        $nonce = wp_create_nonce('twint_check_pairing_status');
        $amount = number_format(
            $this->order->get_total(),
            get_option('woocommerce_price_num_decimals'),
            get_option('woocommerce_price_decimal_sep'),
            get_option('woocommerce_price_thousand_sep')
        );

        $bankApps = [
            'bank-ubs' => 'UBS TWINT',
            'bank-raiffeisen' => 'UBS TWINT',
            'bank-pf' => 'PostFinance TWINT',
            'bank-zkb' => 'ZKB TWINT',
            'bank-cs' => 'Credit Suisse TWINT',
            'bank-bcv' => 'BCV TWINT',
        ];

        $isAndroid = false;
        $isIosDevice = false;
        $androidLink = '';
        if (isset($payLinks['android'])) {
            $isAndroid = true;
        }

        if (isset($payLinks['ios'])) {
            $isIosDevice = true;
        }

        if ($isAndroid && $payLinks['android']) {
            $androidLink = $payLinks['android'];
        }

        if (!empty($_GET['twint_order_paid'])) {
            $isOrderPaid = true;
        } else {
            $isOrderPaid = $this->order->get_status() === \WC_Gateway_Twint_Regular_Checkout::getOrderStatusAfterPaid();
        }

        $isOrderCancelled = $this->order->get_status() === \WC_Gateway_Twint_Regular_Checkout::getOrderStatusAfterCancelled();
        if (!empty($_GET['twint_order_cancelled']) && filter_var($_GET['twint_order_cancelled'], FILTER_VALIDATE_BOOLEAN)) {
            $isOrderCancelled = true;
        }

        if ($isOrderPaid !== true): ?>
            <aside role="dialog" class="text-16 modal-popup twint-modal-slide modal-slide _inner-scroll _show"
                   aria-describedby="modal-content-13" data-role="modal" data-type="popup" tabindex="0"
                   style="z-index: 902;">
                <div data-role="focusable-start" tabindex="0"></div>
                <div class="modal-inner-wrap twint" data-role="focusable-scope">
                    <header class="twint-modal-header sticky top-0 flex justify-between items-center bg-white">
                        <button id="twint-close" data-role="closeBtn" type="button">
                            <svg width="14" height="13" viewBox="0 0 14 13" fill="none"
                                 xmlns="http://www.w3.org/2000/svg">
                                <path d="M1.40001 12.8078L0.692261 12.1L6.29226 6.50001L0.692261 0.900011L1.40001 0.192261L7.00001 5.79226L12.6 0.192261L13.3078 0.900011L7.70776 6.50001L13.3078 12.1L12.6 12.8078L7.00001 7.20776L1.40001 12.8078Z"
                                      fill="#1C1B1F"></path>
                            </svg>
                            <span><?php echo __('Cancel checkout', 'woocommerce-gateway-twint'); ?></span>
                        </button>
                        <img class="twint-logo hidden md:block mr-4"
                             src="<?php echo twint_assets('/images/twint_logo.png'); ?>"
                             alt="TWINT Logo">
                    </header>
                    <div class="twint-modal-content twint-qr-container p-0 md:p-4"
                         id="twint-qr-container"
                         data-role="content">
                        <div id="qr-modal-content" class="text-20" style=""
                             data-mobile="<?php echo $isAndroid || $isIosDevice; ?>"
                             data-is-android-device="<?php echo $isAndroid; ?>"
                             data-is-ios-device="<?php echo $isIosDevice; ?>"
                             data-android-link="<?php echo $payLinks['android']; ?>"
                             data-pairing-id="<?php echo $pairing->getId(); ?>"
                        >
                            <input type="hidden" name="twint_wp_nonce" value="<?php echo $nonce; ?>"
                                   id="twint_wp_nonce">
                            <div class="flex flex-col md:flex-row gap-4 bg-gray-100">
                                <!-- Left Column -->
                                <div class="qr-code d-none d-lg-block md:flex flex flex-1 order-1 md:order-none bg-white p-4 md:rounded-lg items-center justify-center">
                                    <div data-twint-copy-token=""
                                         class="md:flex flex flex-col text-center md:flex-col-reverse ">
                                        <div class="qr-token text-center my-4 md:mt-6">
                                            <input id="qr-token" class="bg-white" type="text"
                                                   value="<?php echo $pairing->getToken(); ?>"
                                                   disabled="disabled"/>
                                        </div>

                                        <div class="md:hidden text-center mt-4">
                                            <button id="btn-copy-token"
                                                    data-clipboard-action="copy"
                                                    data-clipboard-target="#qr-token"
                                                    class="p-4 px-6 !bg-white rounded-lg border-black">
                                                <?php echo __('Copy code', 'woocommerce-gateway-twint'); ?>
                                            </button>
                                        </div>

                                        <div class="flex text-center items-center justify-center" id="qrcode"
                                             title="<?php echo $pairing->getToken(); ?>">
                                            <img src="<?php echo $qrcode; ?>"
                                                 style="display: block;"></div>
                                    </div>
                                </div>

                                <!-- Right Column -->
                                <div class="flex-1 order-0 md:order-1 flex flex-col gap-1 md:gap-4">
                                    <!-- First Div -->
                                    <div class="flex flex-1 bg-white p-4 md:rounded-lg items-center justify-center">
                                        <span id="twint-amount"
                                              class="text-center text-35 inline-block p-4 px-6 text-white bg-black font-semibold">
                                            <?php echo $this->order->get_currency(); ?><?php echo $amount; ?>
                                        </span>
                                    </div>
                                    <!-- Second Div -->
                                    <div class="flex flex-1 bg-white p-4 md:rounded-lg items-center justify-center">
                                        <?php echo get_bloginfo('name'); ?>
                                    </div>

                                    <div class="app-selector md:hidden">
                                        <?php if (isset($payLinks['ios'])): ?>
                                            <div id="twint-ios-container">
                                                <div class="my-6 text-center"><?php echo __('Select your TWINT app:', 'woocommerce-gateway-twint'); ?></div>
                                                <div class="twint-app-container w-3/4 mx-auto justify-center max-w-screen-md mx-auto grid grid-cols-3 gap-4">
                                                    <?php foreach ($payLinks['ios'] as $payLink): ?>
                                                        <?php if (array_key_exists($payLink['name'], $bankApps)): ?>
                                                            <img class="bank-logo shadow-2xl w-64 h-64 mx-auto"
                                                                 src="<?php echo twint_assets('/images/' . $bankApps[$payLink['name']] . '.png'); ?>"
                                                                 data-link="<?php echo $payLink['link']; ?>"
                                                                 alt="<?php echo $bankApps[$payLink['name']]; ?>"/>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </div>

                                                <select id="app-chooser"
                                                        class="twint-select h-55 block my-4 w-full p-4 bg-white text-center appearance-none border-none focus:outline-none focus:ring-0">
                                                    <option value=""><?php echo __('Other banks', 'woocommerce-gateway-twint'); ?></option>
                                                    <?php foreach ($payLinks['ios'] as $payLink): ?>
                                                        <?php if (!array_key_exists($payLink['name'], $bankApps)): ?>
                                                            <option value="{{ payLink.link }}">{{ payLink.name }}
                                                            </option>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        <?php elseif (isset($payLinks['android'])): ?>
                                            <div class="text-center mt-4 px-4" id="twint-android-area">
                                                <a id="twint-android-button"
                                                   class="text-decoration-none block mb-1 bg-black text-white font-bold p-4 rounded-lg text-center hover:bg-gray-800 focus:outline-none focus:ring-gray-600 focus:ring-opacity-75 hover:text-white hover:no-underline"
                                                   href="#">
                                                    <?php echo __('Switch to TWINT app now', 'woocommerce-gateway-twint'); ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>

                                        <div class="qr-code d-none md:flex text-center md:hidden">
                                            <div class="flex items-center justify-center mx-4">
                                                <div class="flex-grow border-b-0 border-t border-solid border-gray-300"></div>
                                                <span class="mx-4 text-black"><?php echo __('or', 'woocommerce-gateway-twint'); ?></span>
                                                <div class="flex-grow border-b-0 border-t border-solid border-gray-300"></div>
                                            </div>

                                            <div class="row qr-code my-3">
                                                <div class="col-9 text-center"><?php echo __('Enter this code in your TWINT app:', 'woocommerce-gateway-twint'); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="qr-code d-none d-lg-block container mx-auto mt-4 text-16">
                                <div class="grid grid-cols-1">
                                    <div class="grid grid-cols-1">
                                        <div id="twint-guide-app" class="flex flex-col items-center p-4 d-none">
                                            <div class="flex justify-center">
                                                <img class="w-55 h-55"
                                                     src="<?php echo twint_assets('/images/icon-twint.svg'); ?>"
                                                     alt="app">
                                            </div>
                                            <div class="text-center mt-4"><?php echo __('Open your TWINT app on your smartphone.', 'woocommerce-gateway-twint'); ?></div>
                                        </div>
                                        <div class="flex flex-col items-center p-4">
                                            <div class="flex justify-center">
                                                <img class="w-55 h-55"
                                                     src="<?php echo twint_assets('/images/icon-scan.svg'); ?>"
                                                     alt="scan">
                                            </div>
                                            <div class="text-center mt-4">
                                                <?php echo __('Scan this QR Code with your TWINT app to complete the checkout.', 'woocommerce-gateway-twint'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div data-role="focusable-end" tabindex="0"></div>
            </aside>
        <?php endif; ?>

        <?php if ($isOrderPaid === true): ?>
        <div class="flashbags">
            <div role="alert" class="alert alert-success alert-has-icon">
                    <span class="icon icon-checkmark-circle">
                        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24"
                             height="24" viewBox="0 0 24 24">
                            <defs>
                                <path d="M24 12c0 6.6274-5.3726 12-12 12S0 18.6274 0 12 5.3726 0 12 0s12 5.3726 12 12zM12 2C6.4772 2 2 6.4772 2 12s4.4772 10 10 10 10-4.4772 10-10S17.5228 2 12 2zM7.7071 12.2929 10 14.5858l6.2929-6.293c.3905-.3904 1.0237-.3904 1.4142 0 .3905.3906.3905 1.0238 0 1.4143l-7 7c-.3905.3905-1.0237.3905-1.4142 0l-3-3c-.3905-.3905-.3905-1.0237 0-1.4142.3905-.3905 1.0237-.3905 1.4142 0z"
                                      id="icons-default-checkmark-circle"></path>
                            </defs>
                            <use xlink:href="#icons-default-checkmark-circle" fill="#758CA3" fill-rule="evenodd"></use></svg>
                    </span>
                <div class="alert-content-container">
                    <div class="alert-content">
                        <?php echo __('Your payment was successful', 'woocommerce-gateway-twint'); ?>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif ($isOrderCancelled === true): ?>
        <div class="flashbags">
            <div role="alert" class="alert alert-danger alert-has-icon">
                        <span class="icon icon-checkmark-circle">
                            <svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="100" height="100"
                                 viewBox="0 0 30 30">
                                <path d="M 7 4 C 6.744125 4 6.4879687 4.0974687 6.2929688 4.2929688 L 4.2929688 6.2929688 C 3.9019687 6.6839688 3.9019687 7.3170313 4.2929688 7.7070312 L 11.585938 15 L 4.2929688 22.292969 C 3.9019687 22.683969 3.9019687 23.317031 4.2929688 23.707031 L 6.2929688 25.707031 C 6.6839688 26.098031 7.3170313 26.098031 7.7070312 25.707031 L 15 18.414062 L 22.292969 25.707031 C 22.682969 26.098031 23.317031 26.098031 23.707031 25.707031 L 25.707031 23.707031 C 26.098031 23.316031 26.098031 22.682969 25.707031 22.292969 L 18.414062 15 L 25.707031 7.7070312 C 26.098031 7.3170312 26.098031 6.6829688 25.707031 6.2929688 L 23.707031 4.2929688 C 23.316031 3.9019687 22.682969 3.9019687 22.292969 4.2929688 L 15 11.585938 L 7.7070312 4.2929688 C 7.5115312 4.0974687 7.255875 4 7 4 z"></path>
                            </svg>
                        </span>
                <div class="alert-content-container">
                    <div class="alert-content">
                        <?php echo __('Your order was cancelled.', 'woocommerce-gateway-twint'); ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif;
    }
}