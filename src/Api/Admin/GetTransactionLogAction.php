<?php

namespace Twint\Woo\Api\Admin;

use Twint\Woo\Api\BaseAction;
use Twint\Woo\Container\Lazy;
use Twint\Woo\Container\LazyLoadTrait;
use Twint\Woo\Helper\XmlHelper;
use Twint\Woo\Model\TransactionLog;
use Twint\Woo\Repository\TransactionRepository;

/**
 * @method TransactionRepository getRepository()
 */
class GetTransactionLogAction extends BaseAction
{
    use LazyLoadTrait;

    protected static array $lazyLoads = ['repository'];

    public function __construct(
        private Lazy|TransactionRepository $repository
    ) {
        add_action('wp_ajax_get_log_transaction_details', [$this, 'getLogTransactionDetails']);
        add_action('wp_ajax_nopriv_get_log_transaction_details', [$this, 'requireLogin']);
    }

    public function getLogTransactionDetails(): void
    {
        if (!isset($_REQUEST['nonce']) || !wp_verify_nonce(
            sanitize_text_field(wp_unslash($_REQUEST['nonce'])),
            'get_log_transaction_details'
        )) {
            exit('The WP Nonce is invalid, please check again!');
        }

        $id = empty($_REQUEST['record_id']) ? '' : sanitize_text_field(wp_unslash($_REQUEST['record_id']));

        /** @var TransactionLog $log */
        $log = $this->getRepository()->get((int) $id);

        ob_start();
        ?>
        <table class="content-table">
            <thead>
            <tr>
                <th><?php echo esc_html__('Order ID', 'twint-woocommerce-extension'); ?></th>
                <th><?php echo esc_html__('API Method', 'twint-woocommerce-extension'); ?></th>
                <th><?php echo esc_html__('Exception', 'twint-woocommerce-extension'); ?></th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td><?php echo esc_html((string) $log->getId()) ?></td>
                <td><span class="badge bg-primary"><?php echo esc_html($log->getApiMethod()); ?></span></td>
                <td><span><?php echo esc_html($log->getExceptionText()); ?></span></td>
            </tr>
            </tbody>
        </table>

        <div class="components-surface components-card woocommerce-store-alerts is-alert-update"
             style="margin: 20px 0;">
            <div class="">
                <div class="components-flex components-card__header components-card-header">
                    <h2 class="components-truncate components-text" style="padding-left: 0;">
                        <?php echo esc_html__('Request', 'twint-woocommerce-extension') . ' ' . esc_html__(
                            'Response',
                            'twint-woocommerce-extension'
                        ); ?>
                    </h2>

                    <div id="request">
                        <label for="request"><?php echo esc_html__('Request', 'twint-woocommerce-extension'); ?></label>
                        <textarea cols="30" rows="6" id="request"
                                  disabled><?php echo esc_html($log->getRequest()); ?></textarea>
                    </div>
                    <div id="response">
                        <label for="request"><?php echo esc_html__('Response', 'twint-woocommerce-extension'); ?></label>
                        <textarea cols="30" rows="6" id="request"
                                  disabled><?php echo esc_html($log->getResponse()); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        <?php foreach ($log->getSoapRequest(true) as $index => $request): ?>
        <div class="components-surface components-card woocommerce-store-alerts is-alert-update"
             style="margin: 20px 0;">
            <div class="">
                <div class="components-flex components-card__header components-card-header">
                    <h2 class="components-truncate components-text" style="padding-left: 0;">
                        <?php echo esc_html($log->getSoapAction(true)[$index]) ?>
                    </h2>

                    <div id="request">
                        <label for="request"><?php echo esc_html__('Request', 'twint-woocommerce-extension'); ?></label>
                        <textarea cols="30" rows="6" id="request"
                                  disabled><?php echo esc_html(XmlHelper::format($request)); ?></textarea>
                    </div>
                    <div id="response">
                        <label for="response"><?php echo esc_html__('Response', 'twint-woocommerce-extension'); ?></label>
                        <textarea cols="30" rows="6" id="request"
                                  disabled><?php echo esc_html(XmlHelper::format($log->getSoapResponse(true)[$index])); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach;
        $result = ob_get_contents();
        ob_end_clean();

        echo wp_json_encode($result);
        die();
    }
}
