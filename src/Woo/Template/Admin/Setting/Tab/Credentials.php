<?php

declare(strict_types=1);

namespace Twint\Woo\Template\Admin\Setting\Tab;

use Twint\Plugin;
use Twint\Woo\Constant\TwintConstant;
use Twint\Woo\Service\SettingService;
use Twint\Woo\Template\Admin\Setting\TabItem;

class Credentials extends TabItem
{
    public static function getKey(): string
    {
        return '_' . str_replace('\\', '_', self::class);
    }

    public static function getLabel(): string
    {
        return __('Credentials', 'woocommerce-gateway-twint');
    }

    public static function fields(): array
    {
        return [
            [
                'name' => SettingService::TEST_MODE,
                'label' => __('Switch to test mode', 'woocommerce-gateway-twint'),
                'type' => 'checkbox',
                'help_text' => '',
                'need_populate' => true,
            ],
            [
                'name' => SettingService::STORE_UUID,
                'label' => __('Store UUID', 'woocommerce-gateway-twint'),
                'placeholder' => __('xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx', 'woocommerce-gateway-twint'),
                'type' => 'text',
                'help_text' => '',
                'need_populate' => true,
            ],
            [
                'name' => SettingService::CERTIFICATE,
                'label' => 'Certificate',
                'type' => 'file',
                'multiple' => false,
                'placeholder' => __('Upload a certificate file (.p12)', 'woocommerce-gateway-twint'),
                'help_text' => __('Certificate file is required', 'woocommerce-gateway-twint'),
                'need_populate' => false,
            ],
            [
                'name' => SettingService::CERTIFICATE_PASSWORD,
                'label' => __('Certificate Password', 'woocommerce-gateway-twint'),
                'type' => 'password',
                'placeholder' => __('Certificate Password', 'woocommerce-gateway-twint'),
                'help_text' => __('Certificate password is required', 'woocommerce-gateway-twint'),
                'need_populate' => false,
            ],
        ];
    }

    public static function getContents(array $data = []): string
    {
        $trigger = Plugin::di('cli.trigger', true);
        $trigger->handle();

        $cliSupport = get_option(TwintConstant::CONFIG_CLI_SUPPORT_OPTION) === 'Yes';

        $isShowedTheButtonUploadNewCert = false;

        ob_start();
        require Plugin::abspath() . 'src/Woo/View/Admin/credentials.php';

        return ob_get_clean();
    }

    public static function allowSaveChanges(): bool
    {
        return true;
    }
}
