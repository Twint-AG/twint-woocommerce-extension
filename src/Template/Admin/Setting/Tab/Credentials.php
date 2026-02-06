<?php

declare(strict_types=1);

namespace Twint\Woo\Template\Admin\Setting\Tab;

use Twint\Woo\Constant\TwintConstant;
use Twint\Woo\Plugin;
use Twint\Woo\Service\SettingService;
use Twint\Woo\Template\Admin\Setting\TabItem;
use Twint\Woo\Utility\CredentialsValidator;

class Credentials extends TabItem
{
    private static SettingService       $settingService;

    private static CredentialsValidator $validator;

    public static function setSettingService(SettingService $service)
    {
        self::$settingService = $service;
    }

    public static function setValidator(CredentialsValidator $validator)
    {
        self::$validator = $validator;
    }

    public static function getKey(): string
    {
        return '_' . str_replace('\\', '_', self::class);
    }

    public static function getLabel(): string
    {
        return __('Credentials', 'twint-woocommerce-extension');
    }

    public static function fields(): array
    {
        $fields = [];

        $flag = 0;

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (isset($_GET['showTwintEnvOptions'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $flag = (int) in_array($_GET['showTwintEnvOptions'], [1, '1', 'yes', 'Yes'], true) !== 0 ? 1 : -1;
        }

        $testMode = get_option(TwintConstant::TEST_MODE) === 'yes';

        if ($flag === 1 || $testMode) {
            $fields = [
                [
                    'name' => TwintConstant::TEST_MODE,
                    'label' => __('Switch to test mode', 'twint-woocommerce-extension'),
                    'type' => 'checkbox',
                    'help_text' => '',
                    'need_populate' => true,
                ],
            ];
        }

        if ($flag === -1) {
            $fields = [];
        }

        return array_merge(
            $fields,
            [
                [
                    'name' => TwintConstant::STORE_UUID,
                    'label' => __('Store UUID', 'twint-woocommerce-extension'),
                    'placeholder' => __('xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx', 'twint-woocommerce-extension'),
                    'type' => 'text',
                    'help_text' => '',
                    'need_populate' => true,
                ],
                [
                    'name' => TwintConstant::CERTIFICATE,
                    'label' => 'Certificate',
                    'type' => 'file',
                    'multiple' => false,
                    'placeholder' => __('Upload a certificate file (.p12)', 'twint-woocommerce-extension'),
                    'help_text' => __('Certificate file is required', 'twint-woocommerce-extension'),
                    'need_populate' => false,
                ],
                [
                    'name' => TwintConstant::CERTIFICATE_PASSWORD,
                    'label' => __('Certificate Password', 'twint-woocommerce-extension'),
                    'type' => 'password',
                    'placeholder' => __('Certificate Password', 'twint-woocommerce-extension'),
                    'help_text' => __('Certificate password is required', 'twint-woocommerce-extension'),
                    'need_populate' => false,
                ],
            ]
        );
    }

    public static function getContents(array $data = []): string
    {
        $validated = get_option(TwintConstant::FLAG_VALIDATED_CREDENTIAL_CONFIG);

        $data['flag_credentials'] = $validated;
        $data['needHideCertificateUpload'] = $validated === TwintConstant::YES;
        $data['status'] = self::validateCredentials();
        $data['fields'] = self::fields();

        $nonce = wp_create_nonce('store_twint_settings');

        $html = '';

        // Check if nonce is not empty
        if (!empty($nonce)) {
            $html .= '<input type="hidden" name="nonce" id="twint_wp_nonce" value="' . $nonce . '">';
        }

        // Add the tab content
        $html .= self::render($data);

        // If save changes is allowed, add the submit button
        if (self::allowSaveChanges()) {
            $html .= '<p class="submit">';
            $html .= '<button type="submit" id="js_twint_button_save" class="button button-primary">';
            $html .= '<span class="button-text">' . __('Save changes', 'twint-woocommerce-extension') . '</span>';
            $html .= '</button>';
            $html .= '</p>';
        }

        return '<form method="post" action="" novalidate="novalidate" enctype="multipart/form-data" autocomplete="off">' . $html . '</form>';
    }

    public static function render(array $data = []): string
    {
        $cliSupport = get_option(TwintConstant::CONFIG_CLI_SUPPORT_OPTION) === 'Yes';

        $trigger = Plugin::di('cli.trigger', false);
        $trigger->handle();

        if (!$cliSupport) {
            list($cliVersion, $isExecutable, $cliInfo, $shellExecAllowed) = Diagnostics::getCliInformation();

            $cliVersionFlag = version_compare($cliVersion, '8.1.0', '>') ? 'passed' : 'error';
            $isExecutableFlag = $isExecutable ? 'passed' : 'error';
            $isExecutableText = $isExecutable ? 'Yes' : 'No';
            $shellExecAllowedText = $shellExecAllowed ? 'Yes' : 'No';
            $shellExecAllowedFlag = $shellExecAllowed ? 'passed' : 'error';

            $cliInfoFlag = 'error';
            if ($cliInfo === 'The TWINT command was successfully executed via the PHP CLI.') {
                $cliInfoFlag = 'passed';
                $cliInfo = __('The TWINT command was successfully executed via the PHP CLI.', 'twint-woocommerce-extension');
            }
        }

        $isShowedTheButtonUploadNewCert = false;

        ob_start();
        require Plugin::abspath() . 'src/View/Admin/credentials.php';

        return ob_get_clean();
    }

    public static function allowSaveChanges(): bool
    {
        return true;
    }

    protected static function validateCredentials(): bool
    {
        $certificateCheck = self::$settingService->getCertificate();

        return self::$validator->validate(
            $certificateCheck,
            get_option(TwintConstant::STORE_UUID, ''),
            get_option(TwintConstant::TEST_MODE) === TwintConstant::YES
        );
    }
}
