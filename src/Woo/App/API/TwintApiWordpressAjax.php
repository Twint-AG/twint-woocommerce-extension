<?php

namespace TWINT\Woo\App\API;

use JetBrains\PhpStorm\NoReturn;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twint\Sdk\Certificate\Pkcs12Certificate;
use Twint\Woo\Includes\Admin\Settings\Tabs\Credential;
use Twint\Woo\Services\PaymentService;
use Twint\Woo\Services\SettingService;
use Twint\Woo\Services\TransactionLogService;
use Twint\Woo\Utility\Twint\CertificateHandler;
use Twint\Woo\Utility\Twint\CredentialValidator;
use Twint\Woo\Utility\Twint\CryptoHandler;

class TwintApiWordpressAjax
{

    private \Twig\Environment $template;

    public function __construct()
    {
        global $TWIG_TEMPLATE_ENGINE;
        $this->template = $TWIG_TEMPLATE_ENGINE;
        add_action('wp_ajax_get_log_transaction_details', [$this, 'getLogTransactionDetails']);
        add_action('wp_ajax_nopriv_get_log_transaction_details', [$this, 'pleaseLogin']);
        add_action('wp_ajax_store_twint_settings', [$this, 'storeTwintSettings']);
        add_action('wp_ajax_nopriv_store_twint_settings', [$this, 'pleaseLogin']);

        add_action('wp_ajax_twint_check_order_status', [$this, 'checkOrderStatus']);
        add_action('wp_ajax_nopriv_twint_check_order_status', [$this, 'pleaseLogin']);
    }

    /**
     * @throws \Exception
     */
    public function checkOrderStatus(): void
    {
        if (!wp_verify_nonce($_REQUEST['nonce'], 'twint_check_order_status')) {
            exit('The WP Nonce is invalid, please check again!');
        }

        $order = wc_get_order($_REQUEST['orderId']);
        if (!$order) {
            exit('The order does not exist.');
        }

        $paymentService = new PaymentService();
        $paymentService->checkOrderStatus($order);

        echo json_encode([
            'success' => true,
            'isOrderPaid' => $order->get_status() === \WC_Gateway_Twint_Regular_Checkout::getOrderStatusAfterPaid(),
            'status' => $order->get_status(),
        ]);

        die();
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     *
     */
    public function getLogTransactionDetails(): void
    {
        if (!wp_verify_nonce($_REQUEST['nonce'], 'get_log_transaction_details')) {
            exit('The WP Nonce is invalid, please check again!');
        }
        $transactionLogService = new TransactionLogService();
        $data = $transactionLogService->getLogTransactionDetails($_REQUEST['record_id']);
        $template = $this->template->load('Layouts/partials/modal/details-content.html.twig');

        $result = $template->render($data);

        $result = json_encode($result);
        echo $result;
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
            $response['message'] = __('Please check again. Your Certificate file, merchant ID or Certificate password is incorrect.', 'woocommerce-gateway-twint');;
        }

        return $response;
    }
}