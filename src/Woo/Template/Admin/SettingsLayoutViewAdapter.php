<?php

declare(strict_types=1);

namespace Twint\Woo\Template\Admin;

use Twint\Woo\Service\SettingService;
use Twint\Woo\Template\Admin\Setting\Tab\Credentials;
use Twint\Woo\Template\Admin\Setting\Tab\ExpressCheckout;
use Twint\Woo\Template\Admin\Setting\Tab\RegularCheckout;
use Twint\Woo\Utility\CredentialsValidator;

class SettingsLayoutViewAdapter
{
    public function __construct(
        private readonly SettingService $settingService,
        private readonly CredentialsValidator $validator,
        private array $data = []
    ) {
    }

    public function render(): void
    {
        $tabs = $this->getTabsConfig();

        $html = '<div class="wrap">
            <h1> ' . __('TWINT Settings', 'woocommerce-gateway-twint') . '</h1>

            <div id="notice-admin-success" class="hidden notice notice-success">
                <p> ' . __('All settings have been saved.', 'woocommerce-gateway-twint') . '</p>
            </div>
            <div id="notice-admin-error" class="hidden notice notice-error is-dismissible"></div>

            <form method="post" action="" novalidate="novalidate" enctype="multipart/form-data" autocomplete="off">
                <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
                    ' . $this->getTabHtml($tabs) . '
                </nav>

                <div class="tab-content">
                    ' . $this->getTabContent() . '
                </div>

            </form>
        </div>';

        echo $html;
    }

    /**
     * Get config of tabs on top of Plugin Settings page
     */
    private function getTabsConfig(): array
    {
        return [
            [
                'key' => Credentials::getKey(),
                'title' => Credentials::getLabel(),
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

    public function getTabHtml(array $tabs): string
    {
        $html = '';

        foreach ($tabs as $tab) {
            // Linked tab
            if (!empty($tab['directLink'])) {
                $html .= '<a href="' . $tab['directLink'] . '" class="nav-tab nav-tab">' . $tab['title'] . '</a>';
                continue;
            }

            // Has tab content as activated item
            $html .= '<a href="' . admin_url() . 'admin.php?page=twint-payment-integration-settings&tab='
                . $tab['key'] . '" class="nav-tab nav-tab nav-tab-active">'
                . $tab['title'] . '</a>';
        }

        return $html;
    }

    public function getTabContent(): string
    {
        $validated = get_option(SettingService::FLAG_VALIDATED_CREDENTIAL_CONFIG);

        $this->data['flag_credentials'] = $validated;
        $this->data['needHideCertificateUpload'] = $validated === SettingService::YES;
        $this->data['status'] = $this->validateCredentials();
        $this->data['fields'] = Credentials::fields();

        $nonce = wp_create_nonce('store_twint_settings');

        $html = '';

        // Check if nonce is not empty
        if (!empty($nonce)) {
            $html .= '<input type="hidden" name="nonce" id="twint_wp_nonce" value="' . $nonce . '">';
        }

        // Add the tab content
        $html .= Credentials::getContents($this->data);

        // If save changes is allowed, add the submit button
        if (Credentials::allowSaveChanges()) {
            $html .= '<p class="submit">';
            $html .= '<button type="submit" id="js_twint_button_save" class="button button-primary">';
            $html .= '<span class="button-text">' . __('Save changes', 'woocommerce-gateway-twint') . '</span>';
            $html .= '</button>';
            $html .= '</p>';
        }

        return $html;
    }

    protected function validateCredentials(): bool
    {
        $certificateCheck = $this->settingService->getCertificate();

        return $this->validator->validate(
            $certificateCheck,
            get_option(SettingService::STORE_UUID, ''),
            get_option(SettingService::TEST_MODE) === SettingService::YES
        );
    }
}
