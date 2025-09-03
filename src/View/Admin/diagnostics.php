<div class="notice notice-info" style="margin-top:12px">
    <p>
        <strong>Diagnostics Information</strong><br>
        The details below provide technical information about your WordPress, WooCommerce, and plugin environment.
        You can also download a full diagnostics report, including log files, which may help identify potential issues
        and will be useful if you need to contact support.
    </p>
</div>


<style>
    .in-valid{
        background-color: rgb(252, 249, 232) !important;
    }
</style>

<table class="widefat striped health-check-table">
    <tbody>
        <?php
            foreach ($info as $item) {
                ?>
                    <tr class="<?php echo esc_attr($item['valid'] ? '' : 'in-valid'); ?>" >
                        <th scope="row"><?php echo esc_html($item['label']); ?></th>
                        <td><?php echo esc_html($item['value']); ?></td>
                    </tr>
                <?php
            }
        ?>      
    </tbody>
</table>

<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:12px">
    <input type="hidden" name="action" value="twint_download_diagnostics">
    <?php wp_nonce_field('twint_download_diagnostics', 'twint_download_diagnostics_nonce'); ?>
    <button type="submit" class="button button-primary">Download Diagnostics</button>
</form>