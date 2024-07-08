<?php

namespace Twint\Woo\Templates;

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twint\Sdk\Certificate\Pkcs12Certificate;
use Twint\Woo\Services\SettingService;
use Twint\Woo\Utility\Twint\CertificateHandler;
use Twint\Woo\Utility\Twint\CryptoHandler;

class SettingsLayoutViewAdapter
{
    private \Twig\Environment $template;
    private array $data;

    const CREDENTIALS = 'credentials';
    const CHECKOUT_OPTIONS = 'checkout_options';
    const ADVANCED_OPTIONS = 'advanced_options';
    private CryptoHandler $encryptor;

    public function __construct($data = [])
    {
        global $TWIG_TEMPLATE_ENGINE;
        $this->template = $TWIG_TEMPLATE_ENGINE;
        $this->data = $data;
        $this->encryptor = new CryptoHandler();
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function render(): void
    {
        $template = $this->template->load('Layouts/SettingsLayout.twig');
//
//        $dataStore = \WC_Data_Store::load('shipping-zone');
//        $rawZones = $dataStore->get_zones();
//        $zones = [];
//        foreach ($rawZones as $rawZone) {
//            $zones[] = new \WC_Shipping_Zone($rawZone);
//        }
//
//        foreach ($zones as $zone) {
//            $zoneShippingMethods = $zone->get_shipping_methods();
//            if (count($zoneShippingMethods)) {
//                foreach ($zoneShippingMethods as $index => $method) {
//                    $method_title = $method->get_method_title();
//                    $method_user_title = $method->get_title();
//                    $method_rate_id = $method->get_rate_id();
//                    printf(
//                        '<li>%s (%s): <strong>%s</strong></li>%s',
//                        $method_user_title,
//                        $method_title,
//                        $method_rate_id,
//                        "\n"
//                    );
//                }
//            }
//        }
//        dd(2);

        /**
         * Tab data
         */
        $defaultTab = self::CREDENTIALS;
        $activatedTab = $_GET['tab'] ?? $defaultTab;
        $dataCreation = [];
        if (isset($_POST['submit'])) {
            if ($activatedTab === self::CREDENTIALS) {
                $dataCreation['merchant_id'] = $_POST['merchant_id'] ?? $this->data['merchant_id'];

                if (!isValidUuid($dataCreation['merchant_id'])) {
                    $this->data['status'] = false;
                    $this->data['error_msg'] = __('Invalid Merchant ID. Must be a valid UUID.', 'woocommerce-gateway-twint');
                    goto RETURN_VIEW;
                }

                $password = $_POST['password'] ?? null;
                $this->data['status'] = true;

                if (!empty($password) && empty($_FILES['certificate']['tmp_name'])) {
                    $this->data['status'] = false;
                    $this->data['error_msg'] = __('You need to provide P12 certificate file.', 'woocommerce-gateway-twint');
                }

                if (!empty($_FILES['certificate']['tmp_name'])) {
                    $file = $_FILES['certificate'];
                    $content = file_get_contents($file['tmp_name']);

                    $extractor = new CertificateHandler();
                    $certificate = $extractor->read((string)$content, $password);

                    if ($certificate instanceof Pkcs12Certificate) {
                        $validatedCertificate = [
                            'certificate' => $this->encryptor->encrypt($certificate->content()),
                            'passphrase' => $this->encryptor->encrypt($certificate->passphrase()),
                        ];

                        update_option(SettingService::CERTIFICATE, $validatedCertificate);
                        update_option(SettingService::MERCHANT_ID, $dataCreation['merchant_id']);
                    } else {
                        $this->data['status'] = false;
                        $this->data['error_msg'] = __('Invalid certificate or password.', 'woocommerce-gateway-twint');
                    }
                }
            } elseif ($activatedTab === self::CHECKOUT_OPTIONS) {
                $checkoutSingle = (isset($_POST[SettingService::EXPRESS_CHECKOUT_SINGLE]) && $_POST[SettingService::EXPRESS_CHECKOUT_SINGLE] === 'on')
                    ? 'yes'
                    : 'no';
                $dataCreation[SettingService::EXPRESS_CHECKOUT_SINGLE] = $checkoutSingle;
                update_option(SettingService::EXPRESS_CHECKOUT_SINGLE, $checkoutSingle);
            } elseif ($activatedTab === self::ADVANCED_OPTIONS) {
                $minutes = $_POST[SettingService::MINUTES_PENDING_WAIT] ?? 30;
                update_option(SettingService::MINUTES_PENDING_WAIT, $minutes);

                $removeDbTables =  (isset($_POST[SettingService::REMOVE_DB_TABLE_WHEN_DISABLING_PLUGIN]) && $_POST[SettingService::REMOVE_DB_TABLE_WHEN_DISABLING_PLUGIN] === 'on')
                    ? 'yes'
                    : 'no';
                update_option(SettingService::REMOVE_DB_TABLE_WHEN_DISABLING_PLUGIN, $removeDbTables);
            }

        } else {
            $dataCreation['merchant_id'] = get_option(SettingService::MERCHANT_ID);

            // Other options setup here...
        }

        RETURN_VIEW:
        $this->data['tabs'] = $this->getTabsConfig();
        $this->data['activated_tab'] = $activatedTab;

        switch ($activatedTab) {
            case self::CREDENTIALS:
                $this->data['fields'] = [
                    [
                        'name' => 'merchant_id',
                        'label' => __('Merchant ID', 'woocommerce-gateway-twint'),
                        'placeholder' => __('Merchant ID', 'woocommerce-gateway-twint'),
                        'type' => 'text',
                        'help_text' => '',
                        'value' => $dataCreation['merchant_id'],
                    ],
                    [
                        'name' => 'certificate',
                        'label' => 'Certificate',
                        'type' => 'file',
                        'multiple' => false,
                        'placeholder' => __('Drag and drop file here', 'woocommerce-gateway-twint'),
                        'help_text' => __('Enter the certificate password for the Twint merchant certificate.', 'woocommerce-gateway-twint'),
                        'value' => '',
                    ],
                    [
                        'name' => 'password',
                        'label' => __('Certificate Password', 'woocommerce-gateway-twint'),
                        'type' => 'password',
                        'placeholder' => __('Password', 'woocommerce-gateway-twint'),
                        'help_text' => __('Please enter the password for the certificate.', 'woocommerce-gateway-twint'),
                        'value' => '',
                    ],
                ];
                $tabContent = $this->template
                    ->load('Layouts/partials/tab-content-pages/GeneralSetting.twig')
                    ->render($this->data);
                break;
            case self::CHECKOUT_OPTIONS:
                $this->data['fields'] = [
                    [
                        'name' => SETTINGService::EXPRESS_CHECKOUT_SINGLE,
                        'label' => __('Checkout the whole current cart (PLP and PDP page)', 'woocommerce-gateway-twint'),
                        'type' => 'checkbox',
                        'placeholder' => '',
                        'help_text' => '',
                        'isChecked' => SettingService::getCheckoutSingle(),
                    ],
                ];

                $tabContent = $this->template
                    ->load('Layouts/partials/tab-content-pages/checkout_options.html.twig')
                    ->render($this->data);
                break;
            case self::ADVANCED_OPTIONS:
                $this->data['fields'] = [
                    [
                        'name' => SETTINGService::MINUTES_PENDING_WAIT,
                        'label' => __('Minutes before Pending order is expired.', 'woocommerce-gateway-twint'),
                        'type' => 'number',
                        'placeholder' => 'Minutes',
                        'help_text' => __('After X minutes, then Pending order status would be changed to Cancelled.', 'woocommerce-gateway-twint'),
                        'value' => SettingService::getMinutesPendingWait(),
                    ],
                    [
                        'name' => SETTINGService::REMOVE_DB_TABLE_WHEN_DISABLING_PLUGIN,
                        'label' => __('Remove all DB tables from the plugin when deactivating?', 'woocommerce-gateway-twint'),
                        'type' => 'checkbox',
                        'placeholder' => '',
                        'help_text' => '',
                        'isChecked' => SettingService::getAutoRemoveDBTableWhenDisabling(),
                    ],
                ];

                $tabContent = $this->template
                    ->load('Layouts/partials/tab-content-pages/advanced_options.html.twig')
                    ->render($this->data);
                break;
            default:
                $tabContent = '';
        }
        $this->data['tabContent'] = $tabContent;

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
                'key' => self::CREDENTIALS,
                'title' => esc_html__('Credentials', 'woocommerce-gateway-twint'),
            ],
            [
                'key' => self::CHECKOUT_OPTIONS,
                'title' => esc_html__('Checkout options', 'woocommerce-gateway-twint'),
            ],
            [
                'key' => self::ADVANCED_OPTIONS,
                'title' => esc_html__('Advanced options', 'woocommerce-gateway-twint'),
            ],
        ];
    }
}