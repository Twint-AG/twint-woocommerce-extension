<?php

declare(strict_types=1);

namespace Twint\Woo\Template\Admin\Setting\Tab;

use Twint\Woo\Constant\TwintConstant;
use Twint\Woo\Plugin;
use Twint\Woo\Template\Admin\Setting\TabItem;

class Credentials extends TabItem
{
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
        $cliSupport = get_option(TwintConstant::CONFIG_CLI_SUPPORT_OPTION) === 'Yes';

        $trigger = Plugin::di('cli.trigger', false);
        $trigger->handle();

        if (!$cliSupport) {
            list($cliVersion, $isExecutable, $cliInfo, $shellExecAllowed) = Plugin::getCliInformation();

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
}
