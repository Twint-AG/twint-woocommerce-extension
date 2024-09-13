<?php

namespace Twint\Woo\Api\Admin;

use Twint\Woo\Api\BaseAction;
use Twint\Woo\Repository\TransactionRepository;
use XmlHelper;

class GetTransactionLogAction extends BaseAction
{
    public function __construct(
        private readonly TransactionRepository $repository
    ) {
        add_action('wp_ajax_get_log_transaction_details', [$this, 'getLogTransactionDetails']);
        add_action('wp_ajax_nopriv_get_log_transaction_details', [$this, 'requireLogin']);
    }

    public function getLogTransactionDetails(): void
    {
        if (!wp_verify_nonce($_REQUEST['nonce'], 'get_log_transaction_details')) {
            exit('The WP Nonce is invalid, please check again!');
        }

        $data = $this->repository->getLogTransactionDetails($_REQUEST['record_id']);

        $soapActions = json_decode($data['soap_action'], true);
        $soapResponses = json_decode($data['soap_response'], true);
        ob_start();
        ?>
        <table class="content-table">
            <thead>
            <tr>
                <th><?= __('Order ID', 'woocommerce-gateway-twint'); ?></th>
                <th><?= __('API Method', 'woocommerce-gateway-twint'); ?></th>
                <th><?= __('Order Status', 'woocommerce-gateway-twint'); ?></th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td><?= $data['order_id']; ?></td>
                <td><span class="badge bg-primary"><?= $data['api_method']; ?></span></td>
                <td><?= $data['order_status']; ?></td>
            </tr>
            </tbody>
        </table>

        <div class="components-surface components-card woocommerce-store-alerts is-alert-update"
             style="margin: 20px 0;">
            <div class="">
                <div class="components-flex components-card__header components-card-header">
                    <h2 class="components-truncate components-text" style="padding-left: 0;">
                        <?= __('Request', 'woocommerce-gateway-twint') . ' ' . __('Response', 'woocommerce-gateway-twint'); ?>
                    </h2>

                    <div id="request">
                        <label for="request"><?= __('Request', 'woocommerce-gateway-twint'); ?></label>
                        <textarea cols="30" rows="6" id="request" disabled><?= $data['request']; ?></textarea>
                    </div>
                    <div id="response">
                        <label for="request"><?= __('Response', 'woocommerce-gateway-twint'); ?></label>
                        <textarea cols="30" rows="6" id="request" disabled><?= $data['response']; ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        <?php foreach (json_decode($data['soap_request']) as $index => $request): ?>
        <div class="components-surface components-card woocommerce-store-alerts is-alert-update"
             style="margin: 20px 0;">
            <div class="">
                <div class="components-flex components-card__header components-card-header">
                    <h2 class="components-truncate components-text" style="padding-left: 0;">
                        <?= $soapActions[$index]; ?>
                    </h2>

                    <div id="request">
                        <label for="request"><?= __('Request', 'woocommerce-gateway-twint'); ?></label>
                        <textarea cols="30" rows="6" id="request"
                                  disabled><?= XmlHelper::format($request); ?></textarea>
                    </div>
                    <div id="response">
                        <label for="response"><?= __('Response', 'woocommerce-gateway-twint'); ?></label>
                        <textarea cols="30" rows="6" id="request"
                                  disabled><?= XmlHelper::format($soapResponses[$index]); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach;
        $result = ob_get_contents();
        ob_end_clean();

        echo json_encode($result);
        die();
    }
}
