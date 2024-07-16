<?php

namespace Twint\Woo\MetaBox;

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twint\Woo\Services\TransactionLogService;

class RefundHistoryMeta
{
    private \Twig\Environment $template;

    public function __construct()
    {
        global $TWIG_TEMPLATE_ENGINE;
        $this->template = $TWIG_TEMPLATE_ENGINE;

        add_action('add_meta_boxes', [$this, 'addShopOrderMetaBoxesTwintRefundHistory']);
    }

    public function addShopOrderMetaBoxesTwintRefundHistory(): void
    {
        add_meta_box(
            'woocommerce-order-twint-refund-history',
            __('Refund History', 'woocommerce-gateway-twint'),
            [$this, 'addCustomTwintRefundHistoryContent'],
            'shop_order',
            'normal',
            'core'
        );
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function addCustomTwintRefundHistoryContent($post): void
    {
        $order = wc_get_order($post->ID);

        $paymentMethod = $order->get_payment_method();
        if ($paymentMethod !== 'twint_regular') {
            return;
        }

        $template = $this->template->load('Layouts/RefundHistory.twig');

        $transactionLogService = new TransactionLogService();
        $logs = $transactionLogService->getLogTransactions($order->get_id());
        $nonce = wp_create_nonce('get_log_refund_order_details');

        echo $template->render([
            'logs' => $logs,
            'nonce' => $nonce,
        ]);
    }
}