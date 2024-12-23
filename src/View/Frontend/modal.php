<?php
//phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
?>

<div id="twint-modal" class="!tw-hidden"
     data-exist-label="<?php use Twint\Woo\Plugin;

// phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- need to match on translated value from core.
echo esc_html__('View cart', 'woocommerce') ?>"
     data-exist-message="<?php echo esc_html__(
         'You have existing products in the shopping cart. Please review your shopping cart before continue.',
         'twint-woocommerce-extension'
     ) ?>"
>
    <div id="twint-overlay" class="tw-fixed tw-inset-0 tw-bg-black tw-opacity-50"></div>
    <div id="twint-modal-content-box" class="modal-inner-wrap tw-shadow-lg tw-w-screen tw-h-screen tw-p-6 tw-z-10 tw-overflow-y-auto <?php echo esc_attr($this->getMdClasses(
        'md:tw-rounded-lg md:tw-h-auto md:tw-max-h-[95vh]'
    )) ?>">
        <header class="twint-modal-header tw-sticky tw-top-0 tw-flex tw-justify-between tw-items-center tw-bg-white tw-py-2 tw-px-4 <?php echo esc_attr($this->getMdClasses(
            'md:tw-rounded-t-lg'
        )) ?>">
            <button id="twint-close"
                    data-default="<?php echo esc_html__('Cancel checkout', 'twint-woocommerce-extension') ?>"
                    data-success="<?php echo esc_html__('Continue shopping', 'twint-woocommerce-extension') ?>"
            >
                <svg width="14" height="13" viewBox="0 0 14 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1.40001 12.8078L0.692261 12.1L6.29226 6.50001L0.692261 0.900011L1.40001 0.192261L7.00001 5.79226L12.6 0.192261L13.3078 0.900011L7.70776 6.50001L13.3078 12.1L12.6 12.8078L7.00001 7.20776L1.40001 12.8078Z" fill="#1C1B1F"></path>
                </svg>
                <span class="tw-ml-2"></span>
            </button>
            <img class="twint-logo tw-hidden <?php echo esc_attr($this->getMdClasses('md:tw-block')) ?>" src="<?php echo esc_attr(Plugin::assets(
                '/images/twint_logo.png'
            )) ?>"
                 alt="TWINT Logo">
        </header>
        <div class="modal-content twint-modal-content tw-p-0 <?php echo esc_attr($this->getMobileClass() . $this->getMdClasses(
            'md:tw-p-4'
        )) ?>">
            <div id="payment-error" class="tw-p-4"><?php echo esc_html__(
                'Payment did not succeed, please try another payment method.',
                'twint-woocommerce-extension'
            ) ?></div>
            <div id="qr-modal-content" class="tw-text-20">
                <input type="hidden" name="twint_wp_nonce" value={nonce} id="twint_wp_nonce"/>
                <div class="tw-flex tw-flex-col  tw-gap-4 tw-bg-gray-100 <?php echo esc_attr($this->getMdClasses('md:tw-flex-row')) ?>">
                    <div class="tw-flex tw-flex-1 tw-order-1 tw-bg-white tw-items-center tw-justify-center <?php echo esc_attr($this->getMdClasses(
                        'md:tw-flex md:tw-order-none md:tw-rounded-lg'
                    )) ?>">
                        <div class="tw-flex tw-flex-col tw-text-center <?php echo esc_attr($this->getMdClasses(
                            'md:tw-flex-col-reverse'
                        )) ?>">
                            <div class="qr-token tw-text-center tw-my-3">
                                <input id="qr-token"
                                       class="tw-bg-white"
                                       type="text"
                                       value={pairingToken}
                                       disabled="disabled"
                                />
                            </div>

                            <div class="tw-text-center tw-my-4 <?php echo esc_attr($this->getMdClasses('md:tw-hidden')) ?>">
                                <button id="twint-copy-btn"
                                        data-clipboard-action="copy"
                                        data-clipboard-target="#qr-token"
                                        data-default="<?php echo esc_html__('Copy code', 'twint-woocommerce-extension') ?>"
                                        data-copied="<?php echo esc_html__('Copied', 'twint-woocommerce-extension') ?>"
                                        class="tw-p-4 tw-px-6 !tw-bg-white tw-rounded-lg tw-border-black">
                                </button>
                            </div>

                            <canvas id="qrcode" class="tw-text-center tw-items-center tw-justify-center tw-m-4
                                    <?php echo esc_attr($this->getMdClasses('md:tw-flex')) ?>"
                                 title={pairingToken}>
                            </canvas>
                        </div>
                    </div>

                    <div class="tw-flex-1 tw-order-0 tw-flex tw-flex-col tw-gap-1 <?php echo esc_attr($this->getMdClasses(
                        'md:tw-gap-4 md:tw-order-1'
                    )) ?>">
                        <div class="tw-flex tw-flex-1 tw-bg-white tw-p-4 tw-items-center tw-justify-center <?php echo esc_attr($this->getMdClasses(
                            'md:tw-rounded-lg'
                        )) ?>">
                                        <span id="twint-amount">
                                            {price}
                                        </span>
                        </div>
                        <div class="tw-flex tw-flex-1 tw-bg-white tw-p-4 tw-items-center tw-justify-center <?php echo esc_attr($this->getMdClasses(
                            'md:tw-rounded-lg'
                        )) ?>">
                            <?php echo esc_html(get_bloginfo('name')) ?>
                        </div>

                        <div class="app-selector <?php echo esc_attr($this->getMdClasses('md:tw-hidden')) ?>">
                            <?php if ($this->isAndroid) {
                                $link = $this->links['android']; ?>

                                <div class="tw-text-center tw-mt-4 tw-px-4">
                                    <a id="twint-addroid-button"
                                       data-href="javascript:window.location = \'' . $link . '\'"
                                       href="javascript:window.location = \'' . $link . '\'">
                                        <?php echo esc_html__('Switch to TWINT app now', 'twint-woocommerce-extension') ?>
                                    </a>
                                </div>
                            <?php }?>

                            <?php //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The HTML is safe and intended for raw output.
                            echo $this->getIosHtml() ?>
                            <div class="tw-text-center <?php echo esc_attr($this->getMdClasses('md:tw-hidden')) ?>">
                                <div class="or-section tw-hidden tw-items-center tw-justify-center tw-mx-4">
                                    <div
                                        class="tw-flex-grow tw-border-b-0 tw-border-t tw-border-solid tw-border-gray-300"></div>
                                    <span class="tw-mx-4 tw-text-black">
                                                <?php echo esc_html__('or', 'twint-woocommerce-extension') ?>
                                            </span>
                                    <div class="tw-flex-grow tw-border-b-0 tw-border-t tw-border-solid tw-border-gray-300"></div>
                                </div>

                                <div class="row tw-my-3">
                                    <div class="col-9 tw-text-center">
                                        <?php echo esc_html__('Enter this code in your TWINT app:', 'twint-woocommerce-extension') ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tw-mx-auto tw-mt-4 tw-text-16 tw-p-4">
                    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2">
                        <div class="twint-scan tw-flex-col tw-items-center <?php echo esc_attr($this->getMdClasses('md:tw-flex')) ?>">
                            <div class="tw-flex tw-justify-center">
                                <img class="!tw-w-55 !tw-h-55"
                                     src="<?php echo esc_attr(Plugin::assets('/images/icon-scan.svg')) ?>"
                                     alt="scan"/>
                            </div>
                            <div class="tw-text-center tw-mt-4">
                                <?php echo esc_html__(
                                    'Scan this QR Code with your TWINT app to complete the checkout.',
                                    'twint-woocommerce-extension'
                                ) ?>
                            </div>
                        </div>
                        <div id="twint-guide-contact" class="tw-flex tw-flex-col tw-items-center">
                            <div class="tw-flex tw-justify-center">
                                <img class="!tw-w-55 !tw-h-55" src="<?php echo esc_attr(Plugin::assets(
                                    '/images/icon-contact.svg'
                                )) ?>" alt="contact">
                            </div>
                            <div class="tw-text-center tw-mt-4"><?php echo esc_html__(
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
