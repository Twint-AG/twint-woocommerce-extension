<?php

namespace Twint\Woo\App\API;

use Symfony\Component\Process\Process;
use Twint\Command\TwintPollCommand;
use Twint\Sdk\Certificate\Pkcs12Certificate;
use Twint\Woo\App\Model\Pairing;
use Twint\Woo\Services\PairingService;
use Twint\Woo\Services\SettingService;
use Twint\Woo\Services\TransactionLogService;
use Twint\Woo\Utility\Twint\CertificateHandler;
use Twint\Woo\Utility\Twint\CredentialValidator;
use Twint\Woo\Utility\Twint\CryptoHandler;

class TwintApiWordpressAjax
{
    private PairingService $pairingService;

    private const TIME_WINDOW_SECONDS = 10; // 10 seconds

    public function __construct()
    {
        add_action('wp_ajax_get_log_transaction_details', [$this, 'getLogTransactionDetails']);
        add_action('wp_ajax_nopriv_get_log_transaction_details', [$this, 'pleaseLogin']);
        add_action('wp_ajax_store_twint_settings', [$this, 'storeTwintSettings']);
        add_action('wp_ajax_nopriv_store_twint_settings', [$this, 'pleaseLogin']);

        add_action('wp_ajax_twint_check_pairing_status', [$this, 'monitorPairing']);
        add_action('wp_ajax_nopriv_twint_check_pairing_status', [$this, 'pleaseLogin']);

        $this->pairingService = new PairingService();
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function monitorPairing(): void
    {
        if (!wp_verify_nonce($_REQUEST['nonce'], 'twint_check_pairing_status')) {
            exit('The WP Nonce is invalid, please check again!');
        }

        $pairingId = $_REQUEST['pairingId'];
        $pairing = $this->pairingService->findById($pairingId);
        if (!$pairing) {
            exit('The pairing for the the order does not exist.');
        }

        if (!$pairing->isFinished() && !$this->isRunning($pairing)) {
            wc_get_logger()->info("[TWINT] - Checking pairing [{$pairingId}]...");

            $process = new Process([
                'php',
                \TwintPayment::plugin_abspath() . 'bin/console',
                TwintPollCommand::COMMAND,
                $pairingId,
            ]);
            $process->setOptions([
                'create_new_console' => true,
            ]);
            $process->disableOutput();
            $process->start();
        }

        echo json_encode([
            'success' => true,
            'isOrderPaid' => $pairing->isFinished(),
            'status' => $pairing->getStatus(),
        ]);

        die();
    }

    protected function isRunning(Pairing $pairing): bool
    {
        return $pairing->getCheckedAt() && $pairing->getCheckedAgo() < self::TIME_WINDOW_SECONDS;
    }

    public function getLogTransactionDetails(): void
    {
        if (!wp_verify_nonce($_REQUEST['nonce'], 'get_log_transaction_details')) {
            exit('The WP Nonce is invalid, please check again!');
        }
        $transactionLogService = new TransactionLogService();
        $data = $transactionLogService->getLogTransactionDetails($_REQUEST['record_id']);

        $soapActions = json_decode($data['soap_action'], true);
        $soapResponses = json_decode($data['soap_response'], true);
        ob_start();
        ?>
        <table class="content-table">
            <thead>
            <tr>
                <th><?php echo __('Order ID', 'woocommerce-gateway-twint'); ?></th>
                <th><?php echo __('API Method', 'woocommerce-gateway-twint'); ?></th>
                <th><?php echo __('Order Status', 'woocommerce-gateway-twint'); ?></th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td><?php echo $data['order_id']; ?></td>
                <td><span class="badge bg-primary"><?php echo $data['api_method']; ?></span></td>
                <td><?php echo $data['order_status']; ?></td>
            </tr>
            </tbody>
        </table>

        <div class="components-surface components-card woocommerce-store-alerts is-alert-update"
             style="margin: 20px 0;">
            <div class="">
                <div class="components-flex components-card__header components-card-header">
                    <h2 class="components-truncate components-text" style="padding-left: 0;">
                        <?php echo __('Request', 'woocommerce-gateway-twint') . ' ' . __('Response', 'woocommerce-gateway-twint'); ?>
                    </h2>

                    <div id="request">
                        <label for="request"><?php echo __('Request', 'woocommerce-gateway-twint'); ?></label>
                        <textarea cols="30" rows="6" id="request" disabled><?php echo $data['request']; ?></textarea>
                    </div>
                    <div id="response">
                        <label for="request"><?php echo __('Response', 'woocommerce-gateway-twint'); ?></label>
                        <textarea cols="30" rows="6" id="request" disabled><?php echo $data['response']; ?></textarea>
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
                        <?php echo $soapActions[$index]; ?>
                    </h2>

                    <div id="request">
                        <label for="request"><?php echo __('Request', 'woocommerce-gateway-twint'); ?></label>
                        <textarea cols="30" rows="6" id="request"
                                  disabled><?php echo xmlBeautiful($request); ?></textarea>
                    </div>
                    <div id="response">
                        <label for="response"><?php echo __('Response', 'woocommerce-gateway-twint'); ?></label>
                        <textarea cols="30" rows="6" id="request"
                                  disabled><?php echo xmlBeautiful($soapResponses[$index]); ?></textarea>
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

    public function storeTwintSettings(): void
    {
        if (!wp_verify_nonce($_REQUEST['nonce'], 'store_twint_settings')) {
            exit('The WP Nonce is invalid, please check again!');
        }

        $certificateResult = [];
        $response = [];
        if (!isValidUuid($_POST[SettingService::STORE_UUID])) {
            $response['status'] = false;
            $response['message'] = __('Invalid Store Uuid. Store Uuid needs to be a UUIDv4.', 'woocommerce-gateway-twint');

            $result = json_encode($response);
            echo $result;

            die();
        } else {
            $storeUuid = $_POST[SettingService::STORE_UUID];
        }

        try {

            $encryptor = new CryptoHandler();

            $pwdKey = SettingService::CERTIFICATE_PASSWORD;
            if ($_POST[$pwdKey] === 'null') {
                $_POST[$pwdKey] = null;
            }
            $certificateKey = SettingService::CERTIFICATE;
            if ($_POST[$certificateKey] === 'null') {
                $_POST[$certificateKey] = null;
            }

            /**
             * Handle validation certificate
             */
            $password = $_POST[$pwdKey] ?? '';
            $response['error_type'] = null;

            if (!empty($password) && empty($_FILES[$certificateKey]['tmp_name'])) {
                $response['status'] = false;
                $response['error_level'] = 'error';
                $response['message'] = __('You need to provide P12 certificate file.', 'woocommerce-gateway-twint');
                $response['error_type'] = 'upload_cert';
            }

            $testMode = $_POST[SettingService::TESTMODE] === 'on' ? SettingService::YES : SettingService::NO;

            $alreadyCheckedSystemCertificate = false;
            if (!empty($_FILES[$certificateKey]['tmp_name'])) {
                $file = $_FILES[$certificateKey];
                $content = file_get_contents($file['tmp_name']);

                $extractor = new CertificateHandler();
                $certificate = $extractor->read((string)$content, $password);

                if ($certificate instanceof Pkcs12Certificate) {
                    $certificateResult = [
                        'certificate' => $encryptor->encrypt($certificate->content()),
                        'passphrase' => $encryptor->encrypt($certificate->passphrase()),
                    ];

                } else {
                    $response['status'] = false;
                    $response['flag_credentials'] = false;
                    $response['error_level'] = 'error';
                    $response['message'] = __('Invalid certificate or password.', 'woocommerce-gateway-twint');
                    $response['error_type'] = 'upload_cert';
                }

                // Call SDK to check system [testMode, certificate, storeUuid]
                $response = $this->checkConfiguration($testMode === SettingService::YES, $storeUuid, $certificateResult);
                $alreadyCheckedSystemCertificate = true;
            }

            if (!$alreadyCheckedSystemCertificate) {
                $certificateCheck = (new SettingService())->getCertificate();
                if (is_null($certificateCheck)) {
                    $certificateCheck = [];
                }
                $response = $this->checkConfiguration($testMode === SettingService::YES, $storeUuid, $certificateCheck);
            }

        } catch (\Exception $exception) {
            wc_get_logger()->error("Error when saving setting " . PHP_EOL . $exception->getMessage());
            $response['status'] = false;
            $response['error_level'] = 'error';
            $response['message'] = $exception->getMessage();
        }

        $result = json_encode($response);
        echo $result;
        die();
    }

    public function pleaseLogin(): void
    {
        echo 'You must login to do next actions';
        die();
    }

    public function checkConfiguration($testMode, string $storeUuid, array $certificate): array
    {
        $response = [];
        $certificateKey = SettingService::CERTIFICATE;
        $isValidTwintConfiguration = (new CredentialValidator())->validate(
            $certificate,
            $storeUuid,
            $testMode
        );

        if ($isValidTwintConfiguration) {
            $response['status'] = true;
            $response['message'] = __('Settings have been saved successfully.', 'woocommerce-gateway-twint');
            update_option($certificateKey, $certificate);
            update_option(SettingService::FLAG_VALIDATED_CREDENTIAL_CONFIG, SettingService::YES);
            update_option(SettingService::TESTMODE, $testMode ? 'yes' : 'no');
            update_option(SettingService::STORE_UUID, $storeUuid);
        } else {
            $response['status'] = false;
            $response['flag_credentials'] = false;

            $response['error_level'] = 'error';
            $response['error_type'] = 'validate_credentials';
            $response['message'] = __('Please check again. Your Certificate file, the Test / Production Mode, Store UUID or Certificate password is incorrect.', 'woocommerce-gateway-twint');;
        }

        return $response;
    }
}
