<?php

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
        ob_start();
        $isShowedTheButtonUploadNewCert = false;
        ?>
        <table class="form-table twint-table-setting" role="presentation">
            <tbody>
            <?php foreach ($data['fields'] as $field): ?>
                <?php if ($isShowedTheButtonUploadNewCert === false): ?>
                    <?php if ($field['name'] === 'plugin_twint_settings_certificate'): ?>
                        <?php $isShowedTheButtonUploadNewCert = true; ?>

                        <tr class="">
                            <th></th>
                            <td>
                                <div class="notify-box notify-success <?php echo $data['needHideCertificateUpload'] === false ? 'hidden' : '' ?>"
                                     style="max-width: 25em;"
                                     id="notice_success_configuration_settings">
                                    <div class="notify-box__content">
                                        <div style="margin-bottom: 10px;">
                                            <?php echo __('Certificate encrypted and stored.', 'woocommerce-gateway-twint') ?>
                                        </div>

                                        <a href="javascript:void(0)" id="upload-new-certificate"
                                           style="margin-top: 7px;">
                                            <?php echo __('Upload new certificate', 'woocommerce-gateway-twint') ?>
                                        </a>

                                        <a href="javascript:void(0)"
                                           id="close-new-certificate"
                                           class="hidden"
                                           style="margin-top: 7px;">
                                            <?php echo __('Close', 'woocommerce-gateway-twint') ?>
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endif; ?>
                <tr class="<?php echo $field['name']; ?> <?php echo (in_array(
                    $field['name'],
                    ['plugin_twint_settings_certificate', 'plugin_twint_settings_certificate_password'],
                    true
                ) and $data['needHideCertificateUpload']) ? 'hidden' : '' ?>">
                    <th scope="row">
                        <label for="<?php echo $field['name']; ?>">
                            <?php echo $field['label']; ?>
                        </label>
                    </th>
                    <td>
                        <?php if ($field['type'] === 'text' || $field['type'] === 'password'): ?>
                            <input name="<?php echo $field['type']; ?>" type="<?php echo $field['type']; ?>"
                                   id="<?php echo $field['name']; ?>"
                                   aria-describedby="tagline-description"
                                <?php if ($field['need_populate'] === true): ?>
                                    value="<?php echo get_option($field['name']); ?>"
                                <?php endif; ?>
                                   placeholder="<?php echo $field['placeholder']; ?>"
                                   class="regular-text"/>
                        <?php elseif ($field['type'] === 'file'): ?>
                            <input class="twint-file-upload"
                                   name="<?php echo $field['name']; ?>"
                                   type="<?php echo $field['type']; ?>"
                                   placeholder="<?php echo $field['placeholder']; ?>"/>

                        <?php elseif ($field['type'] === 'textarea'): ?>
                            <textarea id="<?php echo $field['name']; ?>"
                                      name="<?php echo $field['name']; ?>"
                                      rows="<?php echo $field['rows']; ?>"
                                      type="<?php echo $field['type']; ?>"
                                      class="regular-text twint-field"
                                      placeholder="<?php echo $field['placeholder']; ?>"><?php echo $field['need_populate'] === true ? get_option($field['name']) : ''; ?></textarea>
                        <?php elseif ($field['type'] === 'checkbox'): ?>
                            <fieldset>
                                <legend class="screen-reader-text"><span><?php echo $field['label']; ?></span></legend>
                                <label for="woocommerce_cod_enabled">
                                    <input class=""
                                           type="checkbox"
                                           name="<?php echo $field['name']; ?>"
                                           id="<?php echo $field['name']; ?>"
                                        <?php if ($field['need_populate'] === true): ?>
                                            <?php if (get_option($field['name']) === 'yes'): ?>
                                                checked
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    />
                                    <?php echo $field['label']; ?>
                                </label>
                            </fieldset>
                        <?php endif; ?>


                        <?php if ($field['help_text'] !== ''): ?>
                            <div style="margin-top: 5px;">
                                <small class="text-sm"><i><?php echo $field['help_text']; ?></i></small>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php

                $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    public static function allowSaveChanges(): bool
    {
        return true;
    }
}
