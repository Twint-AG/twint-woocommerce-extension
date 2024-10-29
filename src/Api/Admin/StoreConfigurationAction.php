<?php

declare(strict_types=1);

namespace Twint\Woo\Api\Admin;

use Exception;
use Twint\Sdk\Certificate\Pkcs12Certificate;
use Twint\Woo\Api\BaseAction;
use Twint\Woo\Constant\TwintConstant;
use Twint\Woo\Container\Lazy;
use Twint\Woo\Container\LazyLoadTrait;
use Twint\Woo\Helper\StringHelper;
use Twint\Woo\Service\SettingService;
use Twint\Woo\Utility\CertificateHandler;
use Twint\Woo\Utility\CredentialsValidator;
use Twint\Woo\Utility\CryptoHandler;
use WC_Logger_Interface;

/**
 * @method getSettingService()
 * @method getValidator()
 * @method getCertificateHandler()
 */
class StoreConfigurationAction extends BaseAction
{
    use LazyLoadTrait;

    protected static array $lazyLoads = ['encryptor', 'validator', 'settingService', 'certificateHandler'];

    public function __construct(
        private Lazy|CryptoHandler           $encryptor,
        private Lazy|CredentialsValidator    $validator,
        private readonly WC_Logger_Interface $logger,
        private Lazy|SettingService          $settingService,
        private Lazy|CertificateHandler      $certificateHandler,
    ) {
        add_action('wp_ajax_store_twint_settings', [$this, 'storeTwintSettings']);
        add_action('wp_ajax_nopriv_store_twint_settings', [$this, 'requireLogin']);
    }

    public function storeTwintSettings(): void
    {
        if (!wp_verify_nonce($_REQUEST['nonce'], 'store_twint_settings')) {
            exit('The WP Nonce is invalid, please check again!');
        }

        $certificateResult = [];
        $response = [];
        if (!StringHelper::isValidUuid($_POST[TwintConstant::STORE_UUID])) {
            $response['status'] = false;
            $response['message'] = __('Invalid Store UUID. Store UUID needs to be a UUIDv4.', 'woocommerce-gateway-twint');

            $result = json_encode($response);
            echo $result;

            die();
        }
        $storeUuid = $_POST[TwintConstant::STORE_UUID];

        try {
            $pwdKey = TwintConstant::CERTIFICATE_PASSWORD;
            if ($_POST[$pwdKey] === 'null') {
                $_POST[$pwdKey] = null;
            }
            $certificateKey = TwintConstant::CERTIFICATE;
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
                $response['message'] = __('Upload a certificate file (.p12)', 'woocommerce-gateway-twint');
                $response['error_type'] = 'upload_cert';
            }

            $testMode = $_POST[TwintConstant::TEST_MODE] === 'on' ? TwintConstant::YES : TwintConstant::NO;

            $alreadyCheckedSystemCertificate = false;
            if (!empty($_FILES[$certificateKey]['tmp_name'])) {
                $file = $_FILES[$certificateKey];
                $content = file_get_contents($file['tmp_name']);

                $certificate = $this->getCertificateHandler()->read((string) $content, $password);

                if ($certificate instanceof Pkcs12Certificate) {
                    $certificateResult = [
                        'certificate' => $this->encryptor->encrypt($certificate->content()),
                        'passphrase' => $this->encryptor->encrypt($certificate->passphrase()),
                    ];
                } else {
                    $response['status'] = false;
                    $response['flag_credentials'] = false;
                    $response['error_level'] = 'error';
                    $response['message'] = __('Invalid password', 'woocommerce-gateway-twint');
                    $response['error_type'] = 'upload_cert';

                    $result = json_encode($response);
                    echo $result;
                    die();
                }

                // Call SDK to check system [testMode, certificate, storeUuid]
                $response = $this->checkConfiguration(
                    $testMode === TwintConstant::YES,
                    $storeUuid,
                    $certificateResult
                );
                $alreadyCheckedSystemCertificate = true;
            }

            if (!$alreadyCheckedSystemCertificate) {
                $certificateCheck = $this->getSettingService()->getCertificate();
                if ($certificateCheck === null) {
                    $certificateCheck = [];
                }
                $response = $this->checkConfiguration($testMode === TwintConstant::YES, $storeUuid, $certificateCheck);
            }
        } catch (Exception $exception) {
            $this->logger->error('Error when saving setting ' . PHP_EOL . $exception->getMessage());
            $response['status'] = false;
            $response['error_level'] = 'error';
            $response['message'] = $exception->getMessage();
        }

        $result = json_encode($response);
        echo $result;
        die();
    }

    public function checkConfiguration($testMode, string $storeUuid, array $certificate): array
    {
        $response = [];
        $certificateKey = TwintConstant::CERTIFICATE;
        $isValidTwintConfiguration = $this->getValidator()->validate($certificate, $storeUuid, $testMode);

        if ($isValidTwintConfiguration) {
            $response['status'] = true;
            $response['message'] = __('Settings have been saved successfully.', 'woocommerce-gateway-twint');
            update_option($certificateKey, $certificate);
            update_option(TwintConstant::FLAG_VALIDATED_CREDENTIAL_CONFIG, TwintConstant::YES);
            update_option(TwintConstant::TEST_MODE, $testMode ? 'yes' : 'no');
            update_option(TwintConstant::STORE_UUID, $storeUuid);
        } else {
            $response['status'] = false;
            $response['flag_credentials'] = false;

            $response['error_level'] = 'error';
            $response['error_type'] = 'validate_credentials';
            $response['message'] = __('Invalid credentials. Please check again: Store UUID, certificate and environment (mode)', 'woocommerce-gateway-twint');
        }

        return $response;
    }
}
