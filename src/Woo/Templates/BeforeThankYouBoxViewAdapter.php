<?php

namespace Twint\Woo\Templates;

use chillerlan\QRCode\QRCode;
use Twint\Woo\Services\PairingService;
use Twint\Woo\Services\PaymentService;

class BeforeThankYouBoxViewAdapter
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

        if (!empty($_GET['twint_order_paid'])) {
            $isOrderPaid = true;
        } else {
            $isOrderPaid = $this->order->get_status() === \WC_Gateway_Twint_Regular_Checkout::getOrderStatusAfterPaid();
        }

        $isOrderCancelled = $this->order->get_status() === \WC_Gateway_Twint_Regular_Checkout::getOrderStatusAfterCancelled();
        if (!empty($_GET['twint_order_cancelled']) && filter_var($_GET['twint_order_cancelled'], FILTER_VALIDATE_BOOLEAN)) {
            $isOrderCancelled = true;
        }

        if ($isOrderPaid === true): ?>
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