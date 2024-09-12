<?php

namespace Twint\Woo\Template\Admin\MetaBox;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Twint\Woo\Repository\TransactionRepository;

class TransactionLogMeta
{

    public function __construct()
    {
        add_action('add_meta_boxes', [$this, 'addShopOrderMetaBoxesTwintApiResponse']);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function addShopOrderMetaBoxesTwintApiResponse(): void
    {

        // Support latest / oldest (none-blocks and blocks)
        $screen = class_exists('\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController')
        && wc_get_container()->get(CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled()
            ? wc_get_page_screen_id('shop-order')
            : 'shop_order';
        add_meta_box(
            'woocommerce-order-twint-transaction-log',
            __('Transaction logs', 'woocommerce-gateway-twint'),
            [$this, 'addCustomTwintApiResponseContent'],
            $screen,
            'normal',
            'core'
        );
    }

    public function addCustomTwintApiResponseContent($post): void
    {
        $order = wc_get_order($post->ID);

        $transactionLogService = new TransactionRepository();
        $logs = $transactionLogService->getLogTransactions($order->get_id());
        $nonce = wp_create_nonce('get_log_transaction_details');
        ?>
        <table class="content-table">
            <thead>
            <tr>
                <th><?= __('Order ID', 'woocommerce-gateway-twint'); ?></th>
                <th><?= __('SOAP actions', 'woocommerce-gateway-twint'); ?></th>
                <th><?= __('Order status', 'woocommerce-gateway-twint'); ?></th>
                <th><?= __('Created at', 'woocommerce-gateway-twint'); ?></th>
                <th><?= __('Actions', 'woocommerce-gateway-twint'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= $log['order_id'] ?></td>
                    <td>
                        <?php foreach (json_decode($log['soap_action']) as $action): ?>
                            <span class="badge bg-primary"><?= $action; ?></span>
                        <?php endforeach; ?>
                    </td>
                    <td><?= $log['order_status']; ?></td>
                    <td><?= date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                    <td>
                        <a href="#" class="button button-small button-primary js_view_details"
                           data-nonce="<?= $nonce; ?>"
                           data-record-id="<?= $log['record_id']; ?>">
                            <?= __('View details', 'woocommerce-gateway-twint'); ?>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div class="modal twint-modal">
            <div class="modal-content" style="width: 70%;">
                <div class="modal-body">
                    <span class="close-button">&times;</span>
                    <h3><?= __('Twint transaction log', 'woocommerce-gateway-twint'); ?></h3>
                    <div id="modal-content-details"></div>
                </div>

                <div class="modal-footer">
                    <a href="#" class="button button-small button-primary close-button">
                        <?= __('Close', 'woocommerce-gateway-twint'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
}
