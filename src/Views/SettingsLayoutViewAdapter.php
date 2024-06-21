<?php

namespace TWINT\Views;

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twint\Sdk\Certificate\Pkcs12Certificate;
use TWINT\Utility\Twint\CertificateHandler;
use TWINT\Utility\Twint\CryptoHandler;

class SettingsLayoutViewAdapter
{
    private \Twig\Environment $template;
    private array $data;

    const GENERAL = 'general';
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
        $defaultTab = self::GENERAL;
        $activatedTab = $_GET['tab'] ?? $defaultTab;
        $dataCreation = [];
        if (isset($_POST['submit'])) {
            $dataCreation['merchant_id'] = $_POST['merchant_id'] ?? $this->data['merchant_id'];

            $password = $_POST['password'] ?? null;

            if (!empty($_FILES['certificate'])) {
                $file = $_FILES['certificate'];
                $content = file_get_contents($file['tmp_name']);

                $extractor = new CertificateHandler();
                $certificate = $extractor->read((string)$content, $password);

                if ($certificate instanceof Pkcs12Certificate) {
                    $validatedCertificate = [
                        'certificate' => $this->encryptor->encrypt($certificate->content()),
                        'passphrase' => $this->encryptor->encrypt($certificate->passphrase()),
                    ];

                    $validatedCertificate = [
                        'certificate' => $this->encryptor->encrypt($validatedCertificate['certificate']),
                        'merchant_id' => $dataCreation['merchant_id'],
                    ];

                    update_option('plugin_twint_settings', serialize($validatedCertificate));
                }
            }
        } else {
            $validatedCertificate = get_option('plugin_twint_settings');
            $validatedCertificate = unserialize($validatedCertificate);
            $dataCreation['merchant_id'] = $validatedCertificate['merchant_id'];

            // Other options setup here...
        }

        $this->data['tabs'] = $this->getTabsConfig();
        $this->data['activated_tab'] = $activatedTab;

        switch ($activatedTab) {
            case self::GENERAL:
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
                        'name' => 'password',
                        'label' => 'Password',
                        'type' => 'password',
                        'placeholder' => 'Password',
                        'help_text' => '',
                        'value' => '',
                    ],
                    [
                        'name' => 'certificate',
                        'label' => 'Certificate',
                        'type' => 'file',
                        'multiple' => false,
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
                'key' => self::GENERAL,
                'title' => esc_html__('General', 'twint-payment-integration'),
            ],
        ];
    }
}