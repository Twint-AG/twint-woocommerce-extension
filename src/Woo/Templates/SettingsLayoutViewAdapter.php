<?php

namespace Twint\Woo\Templates;

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

use Twint\Woo\Includes\Admin\Settings\Tabs\Credential;
use Twint\Woo\Includes\Admin\Settings\Tabs\ExpressCheckout;
use Twint\Woo\Includes\Admin\Settings\Tabs\RegularCheckout;
use Twint\Woo\Services\SettingService;
use Twint\Woo\Utility\Twint\CredentialValidator;

class SettingsLayoutViewAdapter
{
    private \Twig\Environment $template;
    private array $data;

    public function __construct($data = [])
    {
        global $TWIG_TEMPLATE_ENGINE;
        $this->template = $TWIG_TEMPLATE_ENGINE;
        $this->data = $data;
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function render(): void
    {
        $template = $this->template->load('Layouts/SettingsLayout.twig');

        /**
         * Tab data
         */
        $defaultTabClass = Credential::getKey();
        $settingTabClassOriginal = $_GET['tab'] ?? $defaultTabClass;

        $settingTabClass = str_replace('_', '\\', $settingTabClassOriginal);
        $this->data['tabs'] = $this->getTabsConfig();
        $this->data['activated_tab'] = $settingTabClassOriginal;
        if ($settingTabClassOriginal === Credential::getKey()) {
            $flagValidatedCredentialsConfig = get_option(SettingService::FLAG_VALIDATED_CREDENTIAL_CONFIG);
            $this->data['flag_credentials'] = $flagValidatedCredentialsConfig;
            $this->data['needHideCertificateUpload'] = $flagValidatedCredentialsConfig === 'yes';
            $this->data['status'] = $this->checkInvalidCredentialsOrNot();
            $this->data['showNoticeCertificate'] = $this->shouldShowNoticeCertificate();
        }

        $this->data['allowSaveChanges'] = $settingTabClass::allowSaveChanges();
        $this->data['fields'] = $settingTabClass::fields();
        $this->data['nonce'] = wp_create_nonce('store_twint_settings');
        $this->data['tabContent'] = $settingTabClass::getContents($this->data);

        echo $template->render($this->data);
    }

    /**
     * Get config of tabs on top of Plugin Settings page
     * @return array
     */
    private function getTabsConfig(): array
    {
        return [
            [
                'key' => Credential::getKey(),
                'title' => Credential::getLabel(),
            ],
            [
                'key' => RegularCheckout::getKey(),
                'title' => RegularCheckout::getLabel(),
                'directLink' => RegularCheckout::directLink(),
            ],
            [
                'key' => ExpressCheckout::getKey(),
                'title' => ExpressCheckout::getLabel(),
                'directLink' => ExpressCheckout::directLink(),
            ],
        ];
    }

    public function shouldShowNoticeCertificate(): bool
    {
        $setting = new SettingService();
        $testMode = get_option(SettingService::TESTMODE, null);
        $certificate = $setting->getCertificate();

        if (empty($certificate) && empty($testMode)) {
            // empty all configs as first installed.
            return false;
        }

        if (empty($certificate)) {
            return true;
        }

        if (empty($testMode)) {
            return true;
        }

        return false;
    }

    public function checkInvalidCredentialsOrNot(): bool
    {
        $certificateCheck = (new SettingService())->getCertificate();
        return (new CredentialValidator())->validate(
            $certificateCheck,
            get_option(SettingService::MERCHANT_ID),
            get_option(SettingService::TESTMODE) === 'yes'
        );
    }
}