<?php

namespace Twint\Woo\Includes\Admin\Settings\Tabs;

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twint\Sdk\Certificate\Pkcs12Certificate;
use Twint\Woo\Abstract\Core\Setting\TabItem;
use Twint\Woo\Services\SettingService;
use Twint\Woo\Utility\Twint\CertificateHandler;
use Twint\Woo\Utility\Twint\CryptoHandler;

class Credential extends TabItem
{
    public static function getKey(): string
    {
        return '_' . str_replace('\\', '_', self::class);
    }

    public static function getLabel(): string
    {
        return __('Credential', 'woocommerce-gateway-twint');
    }

    public static function fields(): array
    {
        return [
            [
                'name' => SettingService::TESTMODE,
                'label' => __('Switch to test mode', 'woocommerce-gateway-twint'),
                'type' => 'checkbox',
                'help_text' => '',
                'need_populate' => true,
            ],
            [
                'name' => SettingService::MERCHANT_ID,
                'label' => __('Merchant ID', 'woocommerce-gateway-twint'),
                'placeholder' => __('xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx', 'woocommerce-gateway-twint'),
                'type' => 'text',
                'help_text' => '',
                'need_populate' => true,
            ],
            [
                'name' => SettingService::CERTIFICATE,
                'label' => 'Certificate',
                'type' => 'file',
                'multiple' => false,
                'placeholder' => __('Upload a certificate file (.p12)', 'woocommerce-gateway-twint'),
                'help_text' => __('Enter the certificate password for the Twint merchant certificate.', 'woocommerce-gateway-twint'),
                'need_populate' => false,
            ],
            [
                'name' => SettingService::CERTIFICATE_PASSWORD,
                'label' => __('Certificate Password', 'woocommerce-gateway-twint'),
                'type' => 'password',
                'placeholder' => __('Certificate Password', 'woocommerce-gateway-twint'),
                'help_text' => __('Please enter the password for the certificate.', 'woocommerce-gateway-twint'),
                'need_populate' => false,
            ],
        ];
    }

    /**
     * @param array $data
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public static function getContents(array $data = []): string
    {
        global $TWIG_TEMPLATE_ENGINE;
        $template = $TWIG_TEMPLATE_ENGINE;

        return $template
            ->load('Layouts/partials/tab-content-pages/setting_master_template.twig')
            ->render($data);
    }

    public static function store(): array
    {
        self::handleTestMode();
    }

    public static function handleTestMode(): bool
    {
        $key = SettingService::TESTMODE;
        try {
            /**
             * Test mode
             */
            $value = $_POST[$key] === 'on' ? 'yes' : 'no';
            update_option($key, $value);

            return true;
        } catch (\Exception $exception) {
            wc_get_logger()->error("Error when saving setting key `{$key}` " . PHP_EOL . $exception->getMessage());
        }

        return false;
    }

    public static function allowSaveChanges(): bool
    {
        return true;
    }
}