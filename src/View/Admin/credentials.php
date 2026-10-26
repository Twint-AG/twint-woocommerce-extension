<?php

use Twint\Woo\Constant\TwintConstant;

if ($cliNoticeLevel === 'warning') { ?>
    <div class="woocommerce-message notice notice-warning" style="margin-top:12px">
        <p>
            <strong><?php echo wp_kses_post(__('PHP CLI not available – background checks run via WP-Cron', 'twint-woocommerce-extension')); ?></strong> <br>
            <?php echo wp_kses_post(
                __('TWINT payments work without PHP CLI. Payment status is checked by the browser while the customer waits and by WP-Cron about once per minute after the browser is closed. Without PHP CLI, background confirmation is slower.', 'twint-woocommerce-extension')
            ); ?>
            <?php if ($wpCronDisabled) { ?>
                <br><strong><?php echo wp_kses_post(__('WP-Cron is disabled. Make sure a server cron job calls wp-cron.php every minute.', 'twint-woocommerce-extension')); ?></strong>
            <?php } else { ?>
                <br><?php echo wp_kses_post(__('Make sure WP-Cron runs reliably; on low-traffic sites, set up a server cron job that calls wp-cron.php every minute.', 'twint-woocommerce-extension')); ?>
            <?php } ?>
        </p>
    </div>
<?php } elseif ($cliNoticeLevel === 'error') { ?>
    <div class="woocommerce-message notice notice-error" style="margin-top:12px">
        <p>
            <strong><?php echo wp_kses_post(__('PHP CLI Not Available', 'twint-woocommerce-extension')); ?></strong> <br>
            <?php echo wp_kses_post(
                __('PHP CLI (Command Line Interface) is missing or misconfigured, and the WP-Cron schedule is not active. Without either, payment status cannot be confirmed after the customer closes the browser. Please refer to the Guide for troubleshooting.', 'twint-woocommerce-extension')
            ); ?>
        <ul id="twint-cli-checks">
            <li data-status="<?php echo esc_html(
                $cliVersionFlag
            ) ?>"><?php echo wp_kses_post(__('PHP CLI version ( >=8.1): ', 'twint-woocommerce-extension')) . esc_html(
                $cliVersion
            ); ?></li>
            <li data-status="<?php echo esc_html(
                $shellExecAllowedFlag
            ) ?>"><?php echo wp_kses_post(__('Function `shell_exec` is allowed: ', 'twint-woocommerce-extension')) . esc_html(
                $shellExecAllowedText
            ); ?></li>
            <li data-status="<?php echo esc_html(
                $isExecutableFlag
            ) ?>"><?php echo wp_kses_post(__('TWINT command is executable: ', 'twint-woocommerce-extension')) . esc_html(
                $isExecutableText
            ); ?></li>
            <li data-status="<?php echo esc_html(
                $cliInfoFlag
            ) ?>"><?php echo wp_kses_post(__('TWINT PHP CLI Command Execution Test: ', 'twint-woocommerce-extension')) . esc_html(
                $cliInfo
            ); ?></li>
        </ul>
            <a href="<?php echo esc_url(
                admin_url(
                    'admin.php?page=twint-payment-integration-settings&tab=_Twint_Woo_Template_Admin_Setting_Tab_Diagnostics'
                )
            ); ?>">
                <?php echo esc_html(__('See diagnostics', 'twint-woocommerce-extension')); ?>
            </a>
        </p>
    </div>
<?php } ?>

<div class="twint-admin">
    <table class=" form-table twint-table-setting" role="presentation">
        <tbody>
            <?php foreach ($data['fields'] as $field) { ?>
                <?php if ($isShowedTheButtonUploadNewCert === false) { ?>
                    <?php if ($field['name'] === 'plugin_twint_settings_certificate') { ?>
                        <?php $isShowedTheButtonUploadNewCert = true; ?>

                        <tr class="">
                            <th></th>
                            <td>
                                <div class="notify-box notify-success <?php echo $data['needHideCertificateUpload'] === false ? 'tw-hidden' : '' ?>"
                                    style="max-width: 333px;"
                                    id="notice_success_configuration_settings">
                                    <div class="notify-box__content">
                                        <div style="margin-bottom: 10px;">
                                            <?php echo esc_html(__('Certificate encrypted and stored.', 'twint-woocommerce-extension')) ?>
                                        </div>

                                        <a href="javascript:void(0)" id="upload-new-certificate"
                                            style="margin-top: 7px;">
                                            <?php echo esc_html(__('Upload new certificate', 'twint-woocommerce-extension')) ?>
                                        </a>

                                        <a href="javascript:void(0)"
                                            id="close-new-certificate"
                                            class="tw-hidden"
                                            style="margin-top: 7px;">
                                            <?php echo esc_html(__('Close', 'twint-woocommerce-extension')) ?>
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } ?>
                <tr class="<?php echo esc_attr($field['name']); ?> <?php echo (in_array(
                    $field['name'],
                    ['plugin_twint_settings_certificate', 'plugin_twint_settings_certificate_password'],
                    true
                ) && $data['needHideCertificateUpload']) ? 'tw-hidden' : '' ?>">
                    <th scope="row">
                        <label for="<?php echo esc_attr($field['name']); ?>">
                            <?php echo esc_html($field['label']); ?>
                        </label>
                    </th>
                    <td>
                        <?php if ($field['type'] === 'text' || $field['type'] === 'password') { ?>
                            <input name="<?php echo esc_attr($field['type']); ?>" type="<?php echo esc_attr($field['type']); ?>"
                                id="<?php echo esc_attr($field['name']); ?>"
                                aria-describedby="tagline-description"
                                <?php if ($field['need_populate'] === true) { ?>
                                value="<?php echo esc_attr(get_option($field['name'])); ?>"
                                <?php } ?>
                                placeholder="<?php echo esc_attr($field['placeholder']); ?>"
                                class="regular-text" />
                            <div class="notify-box notify-error tw-hidden"
                                id="<?php echo esc_attr('error-state_' . $field['name']); ?>">
                                <?php if ($field['name'] === TwintConstant::STORE_UUID) { ?>
                                    <?php echo esc_html(__('Invalid Store UUID. Store UUID needs to be a UUIDv4', 'twint-woocommerce-extension')); ?>
                                <?php } ?>
                            </div>
                        <?php } elseif ($field['type'] === 'file') { ?>
                            <input class="twint-file-upload"
                                name="<?php echo esc_attr($field['name']); ?>"
                                type="<?php echo esc_attr($field['type']); ?>"
                                placeholder="<?php echo esc_attr($field['placeholder']); ?>" /
                                <div class="notify-box notify-error tw-hidden"
                                id="<?php echo esc_attr('error-state_' . $field['name']); ?>">
                            <?php if ($field['name'] === TwintConstant::STORE_UUID) { ?>
                                <?php echo esc_html(__('Test', 'twint-woocommerce-extension')); ?>
                            <?php } ?>
</div>
<?php } elseif ($field['type'] === 'textarea') { ?>
    <textarea id="<?php echo esc_attr($field['name']); ?>"
        name="<?php echo esc_attr($field['name']); ?>"
        rows="<?php echo esc_attr($field['rows']); ?>"
        type="<?php echo esc_attr($field['type']); ?>"
        class="regular-text twint-field"
        placeholder="<?php echo esc_attr($field['placeholder']); ?>">
                                    <?php echo $field['need_populate'] === true ? esc_html(get_option($field['name'])) : ''; ?>
                        </textarea>
<?php } elseif ($field['type'] === 'checkbox') { ?>
    <fieldset>
        <legend class="screen-reader-text"><span><?php echo esc_html($field['label']); ?></span>
        </legend>
        <label for="woocommerce_cod_enabled">
            <input class=""
                type="checkbox"
                name="<?php echo esc_attr($field['name']); ?>"
                id="<?php echo esc_attr($field['name']); ?>"
                <?php if ($field['need_populate'] === true) { ?>
                <?php if (get_option($field['name']) === 'yes') { ?>
                checked
                <?php } ?>
                <?php } ?> />
            <?php echo esc_html($field['label']); ?>
        </label>
    </fieldset>
<?php } ?>

<?php if ($field['help_text'] !== '') { ?>
    <div style="margin-top: 5px;">
        <small class="text-sm"><i><?php echo esc_html($field['help_text']); ?></i></small>
    </div>
<?php } ?>
</td>
</tr>
<?php } ?>
</tbody>
</table>
</div>