<?php

use Twint\Woo\Model\TransactionLog;

?>
<div class="twint-admin">
    <table class="content-table">
        <thead>
            <tr>
                <th><?php echo esc_html__('Order ID', 'twint-woocommerce-extension'); ?></th>
                <th><?php echo esc_html__('API method', 'twint-woocommerce-extension'); ?></th>
                <th><?php echo esc_html__('SOAP actions', 'twint-woocommerce-extension'); ?></th>
                <th><?php echo esc_html__('Created at', 'twint-woocommerce-extension'); ?></th>
                <th><?php echo esc_html__('Actions', 'twint-woocommerce-extension'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            /** @var TransactionLog $log */
            foreach ($logs as $log): ?>
                <tr <?php echo empty($log->getExceptionText()) ? '' : 'class="log-error"' ?>>
                    <td><?php echo esc_html($log->getOrderId()) ?></td>
                    <td><?php echo esc_html($log->getApiMethod()); ?></td>
                    <td>
                        <?php foreach ($log->getSoapAction(true) as $action): ?>
                            <span class="twint-tag"><?php echo esc_html($action); ?></span>
                        <?php endforeach; ?>
                    </td>
                    <td><?php echo esc_html(gmdate('Y-m-d H:i:s', strtotime($log->getCreatedAt()))); ?></td>
                    <td>
                        <a href="#" class="button button-small button-primary js_view_details"
                            data-nonce="<?php echo esc_attr($nonce); ?>"
                            data-record-id="<?php echo esc_attr($log->getId()); ?>">
                            <?php echo esc_html__('View details', 'twint-woocommerce-extension'); ?>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="modal twint-modal">
        <div class="modal-content" style="width: 70%;">
            <div class="modal-header">
                <h3><?php echo esc_html__('Transaction logs', 'twint-woocommerce-extension'); ?></h3>
                <span class="close-button">&times;</span>
            </div>
            <div class="modal-body">
                <div id="modal-content-details"></div>
            </div>
        </div>
    </div>
</div>