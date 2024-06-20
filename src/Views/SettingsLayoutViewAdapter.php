<?php

namespace TWINT\Views;

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class SettingsLayoutViewAdapter
{
    private \Twig\Environment $template;
    private array $data;

    const GENERAL = 'general';

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
        $defaultTab = self::GENERAL;
        $activatedTab = $_GET['tab'] ?? $defaultTab;
        if (isset($_POST['submit'])) {
            $dataCreation['merchant_id'] = isset($_POST['merchant_id'])
                ? json_encode($_POST['merchant_id'])
                : $this->data['merchant_id'];

            $dataCreation['password'] = isset($_POST['password'])
                ? json_encode($_POST['password'])
                : $this->data['password'];
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
                        'value' => '',
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