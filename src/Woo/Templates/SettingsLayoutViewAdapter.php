<?php

namespace Twint\Woo\Templates;

use Twint\Woo\Includes\Admin\Settings\Tabs\Credential;
use Twint\Woo\Includes\Admin\Settings\Tabs\ExpressCheckout;
use Twint\Woo\Includes\Admin\Settings\Tabs\RegularCheckout;
use Twint\Woo\Services\SettingService;
use Twint\Woo\Utility\Twint\CredentialValidator;

class SettingsLayoutViewAdapter
{
    private array $data;

    public function __construct($data = [])
    {
        $this->data = $data;
    }

    public function render(): void
    {
        /**
         * Tab data
         */
        $defaultTabClass = Credential::getKey();
        $settingTabClassOriginal = $_GET['tab'] ?? $defaultTabClass;

        $settingTabClass = str_replace('_', '\\', $settingTabClassOriginal);
        $tabs = $this->getTabsConfig();
        if ($settingTabClassOriginal === Credential::getKey()) {
            $flagValidatedCredentialsConfig = get_option(SettingService::FLAG_VALIDATED_CREDENTIAL_CONFIG);
            $this->data['flag_credentials'] = $flagValidatedCredentialsConfig;
            $this->data['needHideCertificateUpload'] = $flagValidatedCredentialsConfig === SettingService::YES;
            $this->data['status'] = $this->checkInvalidCredentialsOrNot();
        }

        $allowSaveChanges = $settingTabClass::allowSaveChanges();
        $this->data['fields'] = $settingTabClass::fields();
        $nonce = wp_create_nonce('store_twint_settings');
        $tabContent = $settingTabClass::getContents($this->data);

        ?>
        <div class="wrap">
            <h1><?php echo __('TWINT Settings', 'woocommerce-gateway-twint') ?></h1>

            <div id="notice-admin-success" class="d-none notice notice-success">
                <p><?php echo __('All settings have been saved.', 'woocommerce-gateway-twint') ?></p>
            </div>
            <div id="notice-admin-error" class="d-none notice notice-error is-dismissible"></div>

            <form method="post" action="" novalidate="novalidate" enctype="multipart/form-data" autocomplete="off">
                <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
                    <?php foreach ($tabs as $tab): ?>
                        <?php if (!empty($tab['directLink'])): ?>
                            <a href="<?php echo $tab['directLink'] ?>"
                               class="nav-tab nav-tab <?php echo $settingTabClass === $tab['key'] ? 'nav-tab-active' : '' ?>">
                                <?php echo $tab['title']; ?>
                            </a>
                        <?php else: ?>
                            <a href="<?php echo admin_url(); ?>admin.php?page=twint-payment-integration-settings&tab=<?php echo $tab['key'] ?>"
                               class="nav-tab nav-tab">
                                <?php echo $tab['title']; ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </nav>

                <div class="tab-content">
                    <?php if (!empty($nonce)): ?>
                    <?php endif; ?>
                    <input type="hidden" name="nonce" id="twint_wp_nonce" value="<?php echo $nonce; ?>">
                    <!--CONTENT-->
                    <?php echo $tabContent; ?>

                    <?php if ($allowSaveChanges): ?>
                        <p class="submit">
                            <button type="submit" id="js_twint_button_save" class="button button-primary">
                            <span class="button-text">
                                <?php echo __('Save changes', 'woocommerce-gateway-twint') ?></span>
                            </button>
                        </p>
                    <?php endif; ?>
                </div>

            </form>
        </div>
        <?php
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

    public function checkInvalidCredentialsOrNot(): bool
    {
        $certificateCheck = (new SettingService())->getCertificate();
        return (new CredentialValidator())->validate(
            $certificateCheck,
            get_option(SettingService::STORE_UUID),
            get_option(SettingService::TESTMODE) === SettingService::YES
        );
    }
}