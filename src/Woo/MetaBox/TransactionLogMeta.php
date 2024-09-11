<?php

namespace Twint\Woo\MetaBox;

use Twint\Woo\Services\TransactionLogService;

class TransactionLogMeta
{

    public function __construct()
    {
        add_action('add_meta_boxes', [$this, 'addShopOrderMetaBoxesTwintApiResponse']);
    }

    public function addShopOrderMetaBoxesTwintApiResponse(): void
    {
        add_meta_box(
            'woocommerce-order-twint-transaction-log',
            __('Transaction logs', 'woocommerce-gateway-twint'),
            [$this, 'addCustomTwintApiResponseContent'],
            '',
            'normal',
            'core'
        );
    }

    public function addCustomTwintApiResponseContent($post): void
    {
        $order = wc_get_order($post->ID);

        $transactionLogService = new TransactionLogService();
        $logs = $transactionLogService->getLogTransactions($order->get_id());
        $nonce = wp_create_nonce('get_log_transaction_details');
        ?>
        <table class="content-table">
            <thead>
            <tr>
                <th><?php echo __('Order ID', 'woocommerce-gateway-twint'); ?></th>
                <th><?php echo __('SOAP actions', 'woocommerce-gateway-twint'); ?></th>
                <th><?php echo __('Order status', 'woocommerce-gateway-twint'); ?></th>
                <th><?php echo __('Created at', 'woocommerce-gateway-twint'); ?></th>
                <th><?php echo __('Actions', 'woocommerce-gateway-twint'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
            <tr>
                <td><?php echo $log['order_id'] ?></td>
                <td>
                    <?php foreach (json_decode($log['soap_action']) as $action): ?>
                        <span class="badge bg-primary"><?php echo $action; ?></span>
                    <?php endforeach; ?>
                </td>
                <td><?php echo $log['order_status']; ?></td>
                <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                <td>
                    <a href="#" class="button button-small button-primary js_view_details"
                       data-nonce="<?php echo $nonce; ?>"
                       data-record-id="<?php echo $log['record_id']; ?>">
                        <?php echo __('View details', 'woocommerce-gateway-twint'); ?>
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
                    <h3><?php echo __('Twint transaction log', 'woocommerce-gateway-twint'); ?></h3>
                    <div id="modal-content-details"></div>
                </div>

                <div class="modal-footer">
                    <a href="#" class="button button-small button-primary close-button">
                        <?php echo __('Close', 'woocommerce-gateway-twint'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
}