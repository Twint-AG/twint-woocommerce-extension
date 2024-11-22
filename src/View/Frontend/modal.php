<?php
function getMdClasses(string $classes)
{
    return $modal->isMobile ? '' : $classes;
}
//phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
?>

<div id="twint-modal" class="!hidden"
     data-exist-label="<?php use Twint\Woo\Plugin;

// phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- need to match on translated value from core.
echo esc_html__('View cart', 'woocommerce') ?>"
     data-exist-message="<?php echo esc_html__(
         'You have existing products in the shopping cart. Please review your shopping cart before continue.',
         'twint-woocommerce-extension'
     ) ?>"
>
    <div class="fixed inset-0 bg-black opacity-50"></div>
    <div class="modal-inner-wrap shadow-lg w-screen h-screen p-6 z-10 overflow-y-auto <?php echo esc_attr(getMdClasses(
        'md:rounded-lg md:h-auto md:max-h-[95vh]'
    )) ?>">
        <header class="twint-modal-header sticky top-0 flex justify-between items-center bg-white py-2 px-4 <?php echo esc_attr(getMdClasses(
            'md:rounded-t-lg'
        )) ?>">
            <button id="twint-close"
                    data-default="<?php echo esc_html__('Cancel checkout', 'twint-woocommerce-extension') ?>"
                    data-success="<?php echo esc_html__('Continue shopping', 'twint-woocommerce-extension') ?>"
            >
                <svg width="14" height="13" viewBox="0 0 14 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1.40001 12.8078L0.692261 12.1L6.29226 6.50001L0.692261 0.900011L1.40001 0.192261L7.00001 5.79226L12.6 0.192261L13.3078 0.900011L7.70776 6.50001L13.3078 12.1L12.6 12.8078L7.00001 7.20776L1.40001 12.8078Z" fill="#1C1B1F"></path>
                </svg>
                <span class="ml-2"></span>
            </button>
            <img class="twint-logo hidden <?php echo esc_attr(getMdClasses('md:block')) ?>" src="<?php echo esc_attr(Plugin::assets(
                '/images/twint_logo.png'
            )) ?>"
                 alt="TWINT Logo">
        </header>
        <div class="modal-content twint-modal-content p-0 <?php echo esc_attr($this->getMobileClass() . getMdClasses(
            'md:p-4'
        )) ?>">
            <div id="payment-error" class="p-4"><?php echo esc_html__(
                'Payment did not succeed, please try another payment method.',
                'twint-woocommerce-extension'
            ) ?></div>
            <div id="qr-modal-content" class="text-20">
                <input type="hidden" name="twint_wp_nonce" value={nonce} id="twint_wp_nonce"/>
                <div class="flex flex-col  gap-4 bg-gray-100 <?php echo esc_attr(getMdClasses('md:flex-row')) ?>">
                    <div class="flex flex-1 order-1 bg-white items-center justify-center <?php echo esc_attr(getMdClasses(
                        'md:flex md:order-none md:rounded-lg'
                    )) ?>">
                        <div class="flex flex-col text-center <?php echo esc_attr(getMdClasses(
                            'md:flex-col-reverse'
                        )) ?>">
                            <div class="qr-token text-center my-3">
                                <input id="qr-token"
                                       class="bg-white"
                                       type="text"
                                       value={pairingToken}
                                       disabled="disabled"
                                />
                            </div>

                            <div class="text-center my-4 <?php echo esc_attr(getMdClasses('md:hidden')) ?>">
                                <button id="twint-copy-btn"
                                        data-clipboard-action="copy"
                                        data-clipboard-target="#qr-token"
                                        data-default="<?php echo esc_html__('Copy code', 'twint-woocommerce-extension') ?>"
                                        data-copied="<?php echo esc_html__('Copied', 'twint-woocommerce-extension') ?>"
                                        class="p-4 px-6 !bg-white rounded-lg border-black">
                                </button>
                            </div>

                            <canvas id="qrcode" class="text-center items-center justify-center m-4
                                    <?php echo esc_attr(getMdClasses('md:flex')) ?>"
                                 title={pairingToken}>
                            </canvas>
                        </div>
                    </div>

                    <div class="flex-1 order-0 flex flex-col gap-1 <?php echo esc_attr(getMdClasses(
                        'md:gap-4 md:order-1'
                    )) ?>">
                        <div class="flex flex-1 bg-white p-4 items-center justify-center <?php echo esc_attr(getMdClasses(
                            'md:rounded-lg'
                        )) ?>">
                                        <span id="twint-amount">
                                            {price}
                                        </span>
                        </div>
                        <div class="flex flex-1 bg-white p-4 items-center justify-center <?php echo esc_attr(getMdClasses(
                            'md:rounded-lg'
                        )) ?>">
                            <?php echo esc_html(get_bloginfo('name')) ?>
                        </div>

                        <div class="app-selector <?php echo esc_attr(getMdClasses('md:hidden')) ?>">
                            <?php if ($this->isAndroid) {
                                $link = $this->links['android']; ?>

                                <div class="text-center mt-4 px-4">
                                    <a id="twint-addroid-button"
                                       data-href="javascript:window.location = \'' . $link . '\'"
                                       href="javascript:window.location = \'' . $link . '\'">
                                        ' . __('Switch to TWINT app now', 'twint-woocommerce-extension') . '
                                    </a>
                                </div>
                            <?php }?>

                            <?php //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The HTML is safe and intended for raw output.
                            echo $this->getIosHtml() ?>
                            <div class="text-center <?php echo esc_attr(getMdClasses('md:hidden')) ?>">
                                <div class="or-section hidden items-center justify-center mx-4">
                                    <div
                                        class="flex-grow border-b-0 border-t border-solid border-gray-300"></div>
                                    <span class="mx-4 text-black">
                                                <?php echo esc_html__('or', 'twint-woocommerce-extension') ?>
                                            </span>
                                    <div class="flex-grow border-b-0 border-t border-solid border-gray-300"></div>
                                </div>

                                <div class="row my-3">
                                    <div class="col-9 text-center">
                                        <?php echo esc_html__('Enter this code in your TWINT app:', 'twint-woocommerce-extension') ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="container mx-auto mt-4 text-16 p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2">
                        <div class="twint-scan flex-col items-center <?php echo esc_attr(getMdClasses('md:flex')) ?>">
                            <div class="flex justify-center">
                                <img class="w-55 h-55"
                                     src="<?php echo esc_attr(Plugin::assets('/images/icon-scan.svg')) ?>"
                                     alt="scan"/>
                            </div>
                            <div class="text-center mt-4">
                                <?php echo esc_html__(
                                    'Scan this QR Code with your TWINT app to complete the checkout.',
                                    'twint-woocommerce-extension'
                                ) ?>
                            </div>
                        </div>
                        <div id="twint-guide-contact" class="flex flex-col items-center">
                            <div class="flex justify-center">
                                <img class="w-55 h-55" src="<?php echo esc_attr(Plugin::assets(
                                    '/images/icon-contact.svg'
                                )) ?>" alt="contact">
                            </div>
                            <div class="text-center mt-4"><?php echo esc_html__(
                                'Follow the instructions in the app to confirm your order.',
                                'twint-woocommerce-extension'
                            ) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
