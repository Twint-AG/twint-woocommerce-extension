<?php


add_action('wp_ajax_wmufs_admin_notice_ajax_object_save', 'wmufs_admin_notice_ajax_object_callback');

    /**
     * Save option after clicking hide button in WP dashboard.
     *
     * @return void
     */
     function wmufs_admin_notice_ajax_object_callback() {

        $data = isset($_POST['data']) ? sanitize_text_field(wp_unslash($_POST['data'])) : array();

        if ( $data ) {

            // Check valid request form user.
            check_ajax_referer('wmufs_notice_status');

            update_option('wmufs_notice_disable_time', strtotime("+6 Months"));

            $response['message'] = 'success';
            wp_send_json_success($response);
        }

        wp_die();
    }


