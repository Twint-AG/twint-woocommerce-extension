<?php

if ( isset($_GET['max-size-updated']) ) { ?>
    <div class="notice-success notice is-dismissible">
        <p><?php echo esc_html__('Maximum Upload File Size Saved Changed!', 'wp-maximum-upload-file-size');?></p>
    </div>
<?php }

$max_size = get_option('max_file_size');
if ( ! $max_size ) {
    $max_size = wp_max_upload_size();
}
$max_size = $max_size / 1024 / 1024;
$upload_sizes = array(
	'16' => '16 MB',
	'32' => '32 MB',
	'40' => '40 MB',
	'64' => '64 MB',
	'128' => '128 MB',
	'256' => '256 MB',
	'512' => '512 MB',
	'1024' => '1 GB',
	'2048' => '2 GB',
	'3072' => '3 GB',
	'4096' => '4 GB',
	'5120' => '5 GB',
	'10240' => '10 GB',
);

//$current_max_size = self::get_closest($max_size, $upload_sizes);


$wpufs_max_execution_time = get_option('wmufs_maximum_execution_time') != '' ? get_option('wmufs_maximum_execution_time') : ini_get('max_execution_time');


?>

<div class="wrap wmufs_mb_50">
    <h1><span class="dashicons dashicons-upload" style="font-size: inherit; line-height: unset;"></span><?php echo esc_html_e( 'Increase Maximum Upload File Size', 'wp-maximum-upload-file-size' ); ?></h1><br>
    <div class="wmufs_admin_deashboard">
        <!-- Row -->
        <div class="wmufs_row" id="poststuff">

            <!-- Start Content Area -->
            <div class="wmufs_admin_left wmufs_card wmufs-col-8">
                <form method="post">
                    <table class="form-table">
                        <tbody>
                        <tr>
                            <th scope="row"><label for="upload_max_file_size_field">Choose Maximum Upload File Size</label></th>
                            <td>
                                <select id="upload_max_file_size_field" name="upload_max_file_size_field"> <?php
                                    foreach ( $upload_sizes as $key => $size ) {
                                    echo '<option value="' . esc_attr($key) . '" ' . ($key == $max_size ? 'selected' : '') . '>' . esc_html($size) . '</option>';
                                    } ?>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="upload_max_file_size_field">Maximum Execution Time</label></th>
                            <td>
                                <input name="wmufs_maximum_execution_time" type="number" value="<?php echo esc_html($wpufs_max_execution_time);?>">
                                <br><small>Example: 300, 600, 1800, 3600</small>
                            </td>
                        </tr>

                        </tbody>
                    </table>
                    <?php wp_nonce_field('upload_max_file_size_action', 'upload_max_file_size_nonce'); ?>
                    <?php submit_button(); ?>
                </form>

                <table class="wmufs-system-status">

                    <tr>
                        <th><?php esc_html_e('Title','wp-maximum-upload-file-size');?></th>
                        <th><?php esc_html_e('Status', 'wp-maximum-upload-file-size');?></th>
                        <th><?php esc_html_e('Message', 'wp-maximum-upload-file-size');?></th>
                    </tr>
                    <!-- PHP Version -->
                    <?php
                    foreach ( $system_status as $value ) { ?>
                    <tr>
                        <td><?php printf( '%s', esc_html( $value['title'] ) ); ?></td>

                        <td>
                            <?php if ( 1 == $value['status'] ) { ?>
                                <span class="dashicons dashicons-yes"></span>
                            <?php } else { ?>
                                <span class="dashicons dashicons-warning"></span>

                            <?php }; ?>
                        </td>
                        <td>
                            <?php if ( 1 == $value['status'] ) { ?>
                                <p class="wpifw_status_message">  <?php printf( '%s', esc_html( $value['version'] ) ); ?> <?php echo $value['success_message']; //phpcs:ignore ?></p>
                            <?php } else { ?>
                                <?php printf( '%s', esc_html( $value['version'] ) ); ?>
                                <p class="wpifw_status_message"><?php echo $value['error_message']; //phpcs:ignore ?></p>

                            <?php }; ?>

                        </td>
                    </tr>
                    <?php } ?>
                </table>


                <div class="support-ticket">
                    <h2><?php echo esc_html__('Do you need any free help?', 'wp-maximum-upload-file-size'); ?></h2>
                    <a target="_blank" href="<?php echo esc_url_raw('https://wordpress.org/support/plugin/wp-maximum-upload-file-size/');?>"><?php echo esc_html__('Open Ticket', 'wp-maximum-upload-file-size'); ?></a>
                </div>


            </div>
            <!-- End Content Area -->

            <!-- Start Sidebar Area -->
            <div class="wmufs_admin_right_sidebar wmufs_card wmufs-col-4">
                <?php include_once WMUFS_PLUGIN_PATH . 'admin/templates/class-wmufs-sidebar.php'; ?>
            </div>
            <!-- End Sidebar area-->

        </div> <!-- End Row--->
    </div>
</div> <!-- End Wrapper -->

