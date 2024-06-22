<?php

namespace TWINT\Views;

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use TWINT\Factory\ClientBuilder;
use Twint\Sdk\Certificate\Pkcs12Certificate;
use TWINT\Utility\Twint\CertificateHandler;
use TWINT\Utility\Twint\CryptoHandler;

class SettingsLayoutViewAdapter
{
    private \Twig\Environment $template;
    private array $data;

    const CREDENTIALS = 'credentials';
    private CryptoHandler $encryptor;

    public function __construct($data = [])
    {
        global $TWIG_TEMPLATE_ENGINE;
        $this->template = $TWIG_TEMPLATE_ENGINE;
        $this->data = $data;
        $this->encryptor = new CryptoHandler('twint');
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
        $defaultTab = self::CREDENTIALS;
        $activatedTab = $_GET['tab'] ?? $defaultTab;
        $dataCreation = [];
        if (isset($_POST['submit'])) {
            $dataCreation['merchant_id'] = $_POST['merchant_id'] ?? $this->data['merchant_id'];

            if (!isValidUuid($dataCreation['merchant_id'])) {
                $this->data['status'] = false;
                $this->data['error_msg'] = 'Invalid Merchant ID. Must be a valid UUID.';
                goto RETURN_VIEW;
            }

            $password = $_POST['password'] ?? null;
            $this->data['status'] = true;

            if (!empty($password) && empty($_FILES['certificate']['tmp_name'])) {
                $this->data['status'] = false;
                $this->data['error_msg'] = 'You need to provide P12 certificate file.';
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

                    update_option('plugin_twint_settings_certificate', $validatedCertificate);
                    update_option('plugin_twint_settings_merchant_id', $dataCreation['merchant_id']);
                } else {
                    $this->data['status'] = false;
                    $this->data['error_msg'] = 'Invalid certificate or password.';
                }
            }
        } else {
            $dataCreation['merchant_id'] = get_option('plugin_twint_settings_merchant_id');

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
                        'label' => 'Merchant ID',
                        'placeholder' => 'Merchant ID',
                        'type' => 'text',
                        'help_text' => '',
                        'value' => $dataCreation['merchant_id'],
                    ],
                    [
                        'name' => 'certificate',
                        'label' => 'Certificate',
                        'type' => 'file',
                        'multiple' => false,
                        'help_text' => '',
                        'value' => '',
                    ],
                    [
                        'name' => 'password',
                        'label' => 'Certificate Password',
                        'type' => 'password',
                        'placeholder' => 'Password',
                        'help_text' => '',
                        'value' => '',
                    ],
                ];
                $tabContent = $this->template
                    ->load('Layouts/partials/tab-content-pages/GeneralSetting.twig')
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
                'title' => esc_html__('Credentials', 'twint-payment-integration'),
            ],
        ];
    }
}