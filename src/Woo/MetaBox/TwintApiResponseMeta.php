<?php

namespace Twint\Woo\MetaBox;

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twint\Woo\Services\TransactionLogService;

class TwintApiResponseMeta
{
    private \Twig\Environment $template;

    public function __construct()
    {
        global $TWIG_TEMPLATE_ENGINE;
        $this->template = $TWIG_TEMPLATE_ENGINE;

        add_action('add_meta_boxes', [$this, 'addShopOrderMetaBoxesTwintApiResponse']);
    }

    public function addShopOrderMetaBoxesTwintApiResponse(): void
    {
        add_meta_box(
            'woocommerce-order-twint-api-response',
            __('Twint API Response', 'woocommerce'),
            [$this, 'addCustomTwintApiResponseContent'],
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
    public function addCustomTwintApiResponseContent($post): void
    {
        $order = wc_get_order($post->ID);

        $paymentMethod = $order->get_payment_method();
        if ($paymentMethod !== 'twint') {
            return;
        }

        $template = $this->template->load('Layouts/TwintApiResponse.twig');

        $transactionLogService = new TransactionLogService();
        $logs = $transactionLogService->getLogTransactions($order->get_id());
        $nonce = wp_create_nonce('get_log_transaction_details');

        echo $template->render([
            'logs' => $logs,
            'nonce' => $nonce,
        ]);
    }
}