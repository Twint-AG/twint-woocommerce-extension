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
    public const MAX_PASSWORD_LENGTH = 512;
    public const CERTIFICATE_FILE_SIZE = 128 * 1024;
    public const CERTIFICATE_FILE_TYPE = 'application/x-pkcs12';

    protected static array $lazyLoads = ['encryptor', 'validator', 'settingService', 'certificateHandler'];

    public function __construct(
        private Lazy|CryptoHandler           $encryptor,
        private Lazy|CredentialsValidator    $validator,
        private readonly WC_Logger_Interface $logger,
        private Lazy|SettingService          $settingService,
        private Lazy|CertificateHandler      $certificateHandler,
    ) {
        add_action('wp_ajax_store_twint_settings', [$this, 'saveSettings']);
        add_action('wp_ajax_nopriv_store_twint_settings', [$this, 'requireLogin']);
    }

    public function saveSettings(): void
    {
        if (!wp_verify_nonce($_REQUEST['nonce'], 'store_twint_settings')) {
            exit('The WP Nonce is invalid, please check again!');
        }

        $certificateResult = [];
        $response = [];
        $storedCertificate = $this->getSettingService()->getCertificate() ?? [];
        $storeUuid = $this->getStoreUuid();
        $password = $this->getPassword(!empty($storedCertificate));
        $testMode = $_POST[TwintConstant::TEST_MODE] === 'on' ? TwintConstant::YES : TwintConstant::NO;

        try {
            if ($password !== null && $password !== '' && $password !== '0') {
                $certificateContent = $this->getCertificateContent();
                $certificate = $this->getCertificateHandler()->read($certificateContent, $password);

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
                $response = $this->checkConfiguration($testMode === TwintConstant::YES, $storeUuid, $certificateResult);
            } else {
                $response = $this->checkConfiguration($testMode === TwintConstant::YES, $storeUuid, $storedCertificate);
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

    private function getCertificateContent(): string
    {
        $file = $_FILES[TwintConstant::CERTIFICATE] ?? null;
        if ($file['size'] > self::CERTIFICATE_FILE_SIZE || $file['type'] !== self::CERTIFICATE_FILE_TYPE) {
            $this->sendErrorResponse(
                __('Upload a certificate file (.p12)', 'woocommerce-gateway-twint'),
                'upload_cert'
            );
        }

        return file_get_contents($file['tmp_name']);
    }

    private function getStoreUuid(): string
    {
        if (!StringHelper::isValidUuid($_POST[TwintConstant::STORE_UUID])) {
            $response['status'] = false;
            $response['message'] = __('Invalid Store UUID. Store UUID needs to be a UUIDv4.', 'woocommerce-gateway-twint');

            $result = json_encode($response);
            echo $result;

            die();
        }

        return $_POST[TwintConstant::STORE_UUID];
    }

    private function getPassword(bool $allowEmpty): ?string
    {
        $password = $_POST[TwintConstant::CERTIFICATE_PASSWORD] ?? '';
        $password = $password === 'null' ? null : $password;

        if ($this->isInvalidPassword($password, $allowEmpty)) {
            $this->sendErrorResponse(__('Invalid password', 'woocommerce-gateway-twint'), 'upload_cert');
        }

        return $password;
    }

    private function isInvalidPassword(mixed $password, bool $allowEmpty): bool
    {
        if (!$allowEmpty && empty($password)) {
            return true;
        }

        if (!$allowEmpty && !is_string($password)) {
            return true;
        }
        return !$allowEmpty && strlen($password) > self::MAX_PASSWORD_LENGTH;
    }

    private function sendErrorResponse(string $message, string $errorType): void
    {
        $response = [
            'status' => false,
            'flag_credentials' => false,
            'error_level' => 'error',
            'message' => $message,
            'error_type' => $errorType,
        ];

        echo json_encode($response);
        die();
    }

    public function checkConfiguration($testMode, string $storeUuid, array $certificate): array
    {
        $response = [];
        $certificateKey = TwintConstant::CERTIFICATE;
        $isValid = $this->getValidator()->validate($certificate, $storeUuid, $testMode);

        if ($isValid) {
            $response['status'] = true;
            $response['message'] = __('Settings have been saved successfully.', 'woocommerce-gateway-twint');
            update_option($certificateKey, $certificate);
            update_option(TwintConstant::FLAG_VALIDATED_CREDENTIAL_CONFIG, TwintConstant::YES);
            update_option(TwintConstant::TEST_MODE, $testMode ? TwintConstant::YES : TwintConstant::NO);
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
