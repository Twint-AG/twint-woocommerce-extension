<?php use Twint\Woo\Service\SettingService;

if (!$cliSupport) { ?>
    <div class="woocommerce-message notice notice-warning">
        <p>
            <?= wp_kses_post(__('<b>Warning</b>: PHP CLI Not Available', 'woocommerce-gateway-twint')); ?> <br>
            <?= wp_kses_post(
                __('PHP CLI (Command Line Interface) is missing or misconfigured. This extension relies on PHP CLI for essential background processes. Without it, some features may not function properly.', 'woocommerce-gateway-twint')
            ); ?>
        </p>
    </div>
<?php } ?>

<table class="form-table twint-table-setting" role="presentation">
    <tbody>
    <?php foreach ($data['fields'] as $field): ?>
        <?php if ($isShowedTheButtonUploadNewCert === false): ?>
            <?php if ($field['name'] === 'plugin_twint_settings_certificate'): ?>
                <?php $isShowedTheButtonUploadNewCert = true; ?>

                <tr class="">
                    <th></th>
                    <td>
                        <div class="notify-box notify-success <?= $data['needHideCertificateUpload'] === false ? 'hidden' : '' ?>"
                             style="max-width: 25em;"
                             id="notice_success_configuration_settings">
                            <div class="notify-box__content">
                                <div style="margin-bottom: 10px;">
                                    <?= __('Certificate encrypted and stored.', 'woocommerce-gateway-twint') ?>
                                </div>

                                <a href="javascript:void(0)" id="upload-new-certificate"
                                   style="margin-top: 7px;">
                                    <?= __('Upload new certificate', 'woocommerce-gateway-twint') ?>
                                </a>

                                <a href="javascript:void(0)"
                                   id="close-new-certificate"
                                   class="hidden"
                                   style="margin-top: 7px;">
                                    <?= __('Close', 'woocommerce-gateway-twint') ?>
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
        <?php endif; ?>
        <tr class="<?= $field['name']; ?> <?= (in_array(
            $field['name'],
            ['plugin_twint_settings_certificate', 'plugin_twint_settings_certificate_password'],
            true
        ) && $data['needHideCertificateUpload']) ? 'hidden' : '' ?>">
            <th scope="row">
                <label for="<?= $field['name']; ?>">
                    <?= $field['label']; ?>
                </label>
            </th>
            <td>
                <?php if ($field['type'] === 'text' || $field['type'] === 'password'): ?>
                    <input name="<?= $field['type']; ?>" type="<?= $field['type']; ?>"
                           id="<?= $field['name']; ?>"
                           aria-describedby="tagline-description"
                        <?php if ($field['need_populate'] === true): ?>
                            value="<?= get_option($field['name']); ?>"
                        <?php endif; ?>
                           placeholder="<?= $field['placeholder']; ?>"
                           class="regular-text"/>
                    <div class="notify-box notify-error hidden"
                         id="<?php echo 'error-state_' . $field['name']; ?>"
                         style="max-width: 25em; margin-top: 5px">
                        <?php if ($field['name'] === SettingService::STORE_UUID): ?>
                            <?php echo __('Invalid Store UUID. Store UUID needs to be a UUIDv4', 'woocommerce-gateway-twint'); ?>
                        <?php endif; ?>
                    </div>
                <?php elseif ($field['type'] === 'file'): ?>
                    <input class="twint-file-upload"
                           name="<?= $field['name']; ?>"
                           type="<?= $field['type']; ?>"
                           placeholder="<?= $field['placeholder']; ?>"/>

                <?php elseif ($field['type'] === 'textarea'): ?>
                    <textarea id="<?= $field['name']; ?>"
                              name="<?= $field['name']; ?>"
                              rows="<?= $field['rows']; ?>"
                              type="<?= $field['type']; ?>"
                              class="regular-text twint-field"
                              placeholder="<?= $field['placeholder']; ?>"><?= $field['need_populate'] === true ? get_option(
                                  $field['name']
                              ) : ''; ?></textarea>
                <?php elseif ($field['type'] === 'checkbox'): ?>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?= $field['label']; ?></span></legend>
                        <label for="woocommerce_cod_enabled">
                            <input class=""
                                   type="checkbox"
                                   name="<?= $field['name']; ?>"
                                   id="<?= $field['name']; ?>"
                                <?php if ($field['need_populate'] === true): ?>
                                    <?php if (get_option($field['name']) === 'yes'): ?>
                                        checked
                                    <?php endif; ?>
                                <?php endif; ?>
                            />
                            <?= $field['label']; ?>
                        </label>
                    </fieldset>
                <?php endif; ?>

                <?php if ($field['help_text'] !== ''): ?>
                    <div style="margin-top: 5px;">
                        <small class="text-sm"><i><?= $field['help_text']; ?></i></small>
                    </div>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
