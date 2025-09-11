<?php

declare(strict_types=1);

namespace Twint\Woo\Template\Admin;

use AllowDynamicProperties;
use Twint\Woo\Plugin;
use Twint\Woo\Service\SettingService;
use Twint\Woo\Template\Admin\Setting\Tab\Credentials;
use Twint\Woo\Template\Admin\Setting\Tab\Diagnostics;
use Twint\Woo\Template\Admin\Setting\Tab\ExpressCheckout;
use Twint\Woo\Template\Admin\Setting\Tab\RegularCheckout;
use Twint\Woo\Utility\CredentialsValidator;

#[AllowDynamicProperties]
class SettingsLayoutViewAdapter
{
    public function __construct(
        private readonly SettingService       $settingService,
        private readonly CredentialsValidator $validator,
        private array                         $data = []
    ) {
    }

    public function render(): void
    {
        $tabs = $this->getTabsConfig();

        require Plugin::abspath() . 'src/View/Admin/settings.php';
    }

    /**
     * Get config of tabs on top of Plugin Settings page
     */
    private function getTabsConfig(): array
    {
        return [
            [
                'name' => '_Twint_Woo_Template_Admin_Setting_Tab_Credentials',
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
            [
                'name' => '_Twint_Woo_Template_Admin_Setting_Tab_Diagnostics',
                'key' => Diagnostics::getKey(),
                'title' => Diagnostics::getLabel(),
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

            $activatedTab = sanitize_text_field(
                wp_unslash($_REQUEST['tab'] ?? '_Twint_Woo_Template_Admin_Setting_Tab_Credentials')
            );
            $class = $activatedTab === $tab['name'] ? 'nav-tab-active' : '';

            $html .= '<a href="' . admin_url() . 'admin.php?page=twint-payment-integration-settings&tab='
                . $tab['key'] . '" class="nav-tab nav-tab  ' . $class . ' ">'
                . $tab['title'] . '</a>';
        }

        return $html;
    }

    public function getTabContent(): string
    {
        $tab = sanitize_text_field(wp_unslash($_REQUEST['tab'] ?? '_Twint_Woo_Template_Admin_Setting_Tab_Credentials'));
        switch ($tab) {
            case '_Twint_Woo_Template_Admin_Setting_Tab_Credentials':
                Credentials::setSettingService($this->settingService);
                Credentials::setValidator($this->validator);

                return Credentials::getContents($this->data);

            case '_Twint_Woo_Template_Admin_Setting_Tab_Diagnostics':
                return Diagnostics::getContents($this->data);
        }

        return '';
    }
}
