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
        if (!isset($_REQUEST['nonce']) || !wp_verify_nonce(
            //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            wp_unslash($_REQUEST['nonce']),
            'store_twint_settings'
        )) {
            exit('The WP Nonce is invalid, please check again!');
        }

        $certificateResult = [];
        $response = [];
        $storedCertificate = $this->getSettingService()->getCertificate() ?? [];
        $storeUuid = $this->getStoreUuid(sanitize_text_field(wp_unslash($_POST[TwintConstant::STORE_UUID] ?? '')));
        $password = $this->getPassword(
            sanitize_text_field(wp_unslash($_POST[TwintConstant::CERTIFICATE_PASSWORD] ?? '')),
            !empty($storedCertificate)
        );
        $testMode = isset($_POST[TwintConstant::TEST_MODE]) && $_POST[TwintConstant::TEST_MODE] === 'on' ? TwintConstant::YES : TwintConstant::NO;

        try {
            if ($password !== null && $password !== '' && $password !== '0') {
                // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                $file = $_FILES[TwintConstant::CERTIFICATE] ?? null;
                // phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

                $certificateContent = $this->getCertificateContent($file);
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
                    $response['message'] = __('Invalid password', 'twint-woocommerce-extension');
                    $response['error_type'] = 'upload_cert';

                    echo wp_json_encode($response);
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

        echo wp_json_encode($response);
        die();
    }

    private function getCertificateContent($file): string
    {
        if ($file['size'] > self::CERTIFICATE_FILE_SIZE || $file['type'] !== self::CERTIFICATE_FILE_TYPE) {
            $this->sendErrorResponse(
                __('Upload a certificate file (.p12)', 'twint-woocommerce-extension'),
                'upload_cert'
            );
        }

        // Use WP_Filesystem to read the file content
        global $wp_filesystem;

        WP_Filesystem();

        return $wp_filesystem->get_contents($file['tmp_name']);
    }

    private function getStoreUuid(string $string): string
    {
        if (!StringHelper::isValidUuid($string)) {
            $response['status'] = false;
            $response['message'] = __('Invalid Store UUID. Store UUID needs to be a UUIDv4.', 'twint-woocommerce-extension');

            echo wp_json_encode($response);
            die();
        }

        return $string;
    }

    private function getPassword(string $password, bool $allowEmpty): ?string
    {
        $password = $password === 'null' ? null : $password;

        if ($this->isInvalidPassword($password, $allowEmpty)) {
            $this->sendErrorResponse(__('Invalid password', 'twint-woocommerce-extension'), 'upload_cert');
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

        echo wp_json_encode($response);
        die();
    }

    public function checkConfiguration($testMode, string $storeUuid, array $certificate): array
    {
        $response = [];
        $certificateKey = TwintConstant::CERTIFICATE;
        $isValid = $this->getValidator()->validate($certificate, $storeUuid, $testMode);

        if ($isValid) {
            $response['status'] = true;
            $response['message'] = __('Settings have been saved successfully.', 'twint-woocommerce-extension');
            update_option($certificateKey, $certificate);
            update_option(TwintConstant::FLAG_VALIDATED_CREDENTIAL_CONFIG, TwintConstant::YES);
            update_option(TwintConstant::TEST_MODE, $testMode ? TwintConstant::YES : TwintConstant::NO);
            update_option(TwintConstant::STORE_UUID, $storeUuid);
        } else {
            $response['status'] = false;
            $response['flag_credentials'] = false;

            $response['error_level'] = 'error';
            $response['error_type'] = 'validate_credentials';
            $response['message'] = __('Invalid credentials. Please check again: Store UUID, certificate and environment (mode)', 'twint-woocommerce-extension');
        }

        return $response;
    }
}
