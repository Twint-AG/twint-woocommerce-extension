<?php

declare(strict_types=1);

namespace Twint\Woo\Template\Admin\Setting\Tab;

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
                'help_text' => __('Enter the certificate password for the Twint merchant certificate.', 'woocommerce-gateway-twint'),
                'need_populate' => false,
            ],
            [
                'name' => SettingService::CERTIFICATE_PASSWORD,
                'label' => __('Certificate Password', 'woocommerce-gateway-twint'),
                'type' => 'password',
                'placeholder' => __('Certificate Password', 'woocommerce-gateway-twint'),
                'help_text' => __('Please enter the password for the certificate.', 'woocommerce-gateway-twint'),
                'need_populate' => false,
            ],
        ];
    }

    public static function getContents(array $data = []): string
    {
        $showButton = false;

        $getField = static function ($field) {
            $html = '';

            if ($field['type'] === 'text' || $field['type'] === 'password') {
                $value = ($field['need_populate']) ? 'value="' . get_option($field['name']) . '"' : '';

                return '<input name="' . $field['type'] . '" type="' . $field['type'] . '"
                       id="' . $field['name'] . '"
                       aria-describedby="tagline-description"
                        ' . $value . '
                       placeholder="' . $field['placeholder'] . '"
                       class="regular-text"/>';
            }

            if ($field['type'] === 'file') {
                return '<input class="twint-file-upload"
                           name="' . $field['name'] . '"
                           type="' . $field['type'] . '"
                           placeholder="' . $field['placeholder']
                    . '"/>';
            }

            if ($field['type'] === 'textarea') {
                return '<textarea id="' . $field['name'] . '"
                      name="' . $field['name'] . '"
                      rows="' . $field['rows'] . '"
                      type="' . $field['type'] . '"
                      class="regular-text twint-field"
                      placeholder="' . $field['placeholder'] . '">'
                    . ($field['need_populate'] === true ? get_option($field['name']) : '') .
                    '</textarea>';
            }

            if ($field['type'] === 'checkbox') {
                $checked = ($field['need_populate'] === true && get_option(
                    $field['name']
                ) === 'yes') ? ' checked ' : '';
                return '
                    <fieldset>
                        <legend class="screen-reader-text"><span>' . $field['label'] . '</span></legend>
                        <label for="woocommerce_cod_enabled">
                            <input class=""
                                   type="checkbox"
                                   name="' . $field['name'] . '"
                                   id="' . $field['name'] . '"' . $checked . ' />
                            ' . $field['label'] . '
                        </label>
                    </fieldset>';
            }

            if ($field['type'] === 'checkbox') {
                $checked = ($field['need_populate'] === true && get_option(
                    $field['name']
                ) === 'yes') ? ' checked ' : '';
                return '
                <fieldset>
                    <legend class="screen-reader-text"><span>' . $field['label'] . '</span></legend>
                    <label for="woocommerce_cod_enabled">
                        <input class=""
                               type="checkbox"
                               name="' . $field['name'] . '"
                               id="' . $field['name'] . '"' . $checked . ' />
                    ' . $field['label'] . '
                    </label>
                </fieldset>';
            }

            return $html;
        };

        $getFields = static function () use ($data, $showButton, $getField) {
            $html = '';
            foreach ($data['fields'] as $field) {
                if (!$showButton && $field['name'] === 'plugin_twint_settings_certificate') {
                    $showButton = true;
                    $html .= '<tr class="">
                            <th></th>
                            <td>
                                <div class="notify-box notify-success ' . ($data['needHideCertificateUpload'] === false ? 'hidden' : '') . '"
                                     style="max-width: 25em;"
                                     id="notice_success_configuration_settings">
                                    <div class="notify-box__content">
                                        <div style="margin-bottom: 10px;">
                                            ' . __('Certificate encrypted and stored . ', 'woocommerce - gateway - twint') . '
                                        </div>

                                        <a href="javascript:void(0)" id="upload-new-certificate" style="margin-top: 7px;">
                                            ' . __('Upload new certificate', 'woocommerce - gateway - twint') . '
                                        </a>

                                        <a href="javascript:void(0)" id="close-new-certificate" class="hidden" style="margin-top: 7px;">
                                            ' . __('Close', 'woocommerce - gateway - twint') . '
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>';
                }

                $class = (in_array(
                    $field['name'],
                    ['plugin_twint_settings_certificate', 'plugin_twint_settings_certificate_password'],
                    true
                ) && $data['needHideCertificateUpload']) ? 'hidden' : '';

                $helpText = ($field['help_text'] !== '') ? '<div style="margin-top: 5px;">
                                 <small class="text-sm"><i>' . $field['help_text'] . '</i></small>
                              </div>' : '';

                $html .= '
                <tr class="' . $field['name'] . ' ' . $class . '">
                    <th scope="row">
                        <label for="' . $field['name'] . '">' . $field['label'] . '</label>
                    </th>
                    <td>
                        ' . $getField($field) . $helpText . '
                    </td>
                </tr>';
            }

            return $html;
        };

        return '
            <table class="form-table twint-table-setting" role="presentation">
                <tbody>
                    ' . $getFields() . '
                </tbody>
            </table>
        ';
    }

    public static function allowSaveChanges(): bool
    {
        return true;
    }
}
