<?php

/**
 * Class Codepopular_WMUFS
 */
class Codepopular_WMUFS
{
    static function init() {

        if ( is_admin() ) {
            add_action('admin_enqueue_scripts', array( __CLASS__, 'wmufs_style_and_script' ));
            add_action('admin_menu', array( __CLASS__, 'upload_max_file_size_add_pages' ));
            add_filter('plugin_action_links_' . WMUFS_PLUGIN_BASENAME, array( __CLASS__, 'plugin_action_links' ));
            add_filter('plugin_row_meta', array( __CLASS__, 'plugin_meta_links' ), 10, 2);
            add_filter('admin_footer_text', array( __CLASS__, 'admin_footer_text' ));

            if ( isset($_POST['upload_max_file_size_field']) ) {
                $retrieved_nonce = isset($_POST['upload_max_file_size_nonce']) ? sanitize_text_field(wp_unslash($_POST['upload_max_file_size_nonce'])) : '';
                if ( ! wp_verify_nonce($retrieved_nonce, 'upload_max_file_size_action') ) {
                    die('Failed security check');
                }
                $max_size = (int) $_POST['upload_max_file_size_field'] * 1024 * 1024;
                $max_execution_time = isset($_POST['wmufs_maximum_execution_time']) ? sanitize_text_field(wp_unslash( (int) $_POST['wmufs_maximum_execution_time'])) : '';
                update_option('wmufs_maximum_execution_time', $max_execution_time);
                update_option('max_file_size', $max_size);
                wp_safe_redirect(admin_url('upload.php?page=upload_max_file_size&max-size-updated=true'));
            }
        }

        add_filter('upload_size_limit', array( __CLASS__, 'upload_max_increase_upload' ));
    }

    /**
     * Load Plugin Style and Scripts.
     * @return string
     */

    static function wmufs_style_and_script() {
        wp_enqueue_style('wmufs-admin-style', WMUFS_PLUGIN_URL . 'assets/css/wmufs.min.css', null, WMUFS_PLUGIN_VERSION);

        wp_enqueue_script('wmufs-admin', WMUFS_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), WMUFS_PLUGIN_VERSION, true);

        // Ajax admin localization.
        $admin_notice_nonce = wp_create_nonce('wmufs_notice_status');
        wp_localize_script(
            'wmufs-admin',
            'wmufs_admin_notice_ajax_object',
            array(
                'wmufs_admin_notice_ajax_url' => admin_url('admin-ajax.php'),
                'nonce'              => $admin_notice_nonce,
            )
        );
    }


    // get plugin version from header
    static function get_plugin_version() {
        $plugin_data = get_file_data(__FILE__, array( 'version' => 'Version' ), 'plugin');

        return $plugin_data['version'];
    } // get_plugin_version


    // test if we're on plugin's page
    static function is_plugin_page() {
        $current_screen = get_current_screen();

        if ( $current_screen->id == 'media_page_upload_max_file_size' ) {
            return true;
        } else {
            return false;
        }
    } // is_plugin_page


    // add settings link to plugins page
    static function plugin_action_links( $links ) {
        $settings_link = '<a href="' . admin_url('upload.php?page=upload_max_file_size') . '" title="Adjust Max File Upload Size Settings">Settings</a>';

        array_unshift($links, $settings_link);

        return $links;
    } // plugin_action_links


    // add links to plugin's description in plugins table
    static function plugin_meta_links( $links, $file ) {
        $support_link = '<a target="_blank" href="https://wordpress.org/support/plugin/wp-maximum-upload-file-size/" title="Get help">Support</a>';


        if ( $file == plugin_basename(__FILE__) ) {
            $links[] = $support_link;
        }

        return $links;
    } // plugin_meta_links


    // additional powered by text in admin footer; only on plugin's page
    static function admin_footer_text( $text ) {
        if ( ! self::is_plugin_page() ) {
            return $text;
        }

        $text = '<span id="footer-thankyou">If you like <strong><ins>WP Maximum Upload File Size</ins></strong> please leave us a <a target="_blank" style="color:#f9b918" href="https://wordpress.org/support/view/plugin-reviews/wp-maximum-upload-file-size?rate=5#postform">★★★★★</a> rating. A huge thank you in advance!</span>';
        return $text;
    } // admin_footer_text


    /**
     * Add menu pages
     *
     * @since 1.0
     *
     * @return null
     *
     */
    static function upload_max_file_size_add_pages() {
        // Add a new menu on main menu
        add_submenu_page(
            'upload.php', // Parent Slug.
            'Increase Max Upload File Size', // Page Title.
            'Increase Upload Limit', // Menu Title
            'manage_options',
            'upload_max_file_size',
            [ __CLASS__, 'upload_max_file_size_dash' ]
        );

    }

    /**
     * Get closest value from array
     * @param $search
     * @param $arr
     * @return mixed|null
     */
    static function get_closest( $search, $arr ) {
        $closest = null;
        foreach ( $arr as $item ) {
            if ( $closest === null || abs($search - $closest) > abs($item - $search) ) {
                $closest = $item;
            }
        }
        return $closest;
    } // get_closest


    /**
     * Dashboard Page
     */
    static function upload_max_file_size_dash() {

        include_once(WMUFS_PLUGIN_PATH . 'inc/class-wmufs-helper.php');
        include_once WMUFS_PLUGIN_PATH . 'admin/templates/class-wmufs-template.php';

        add_action('admin_head', [ __CLASS__, 'wmufs_remove_admin_action' ]);
    }


    /**
     * Remove admin notices in admin page.
     *
     * @return array|mixed.
     */
    static function wmufs_remove_admin_action() {
        remove_all_actions('user_admin_notices');
        remove_all_actions('admin_notices');
    }

    /**
     * Filter to increase max_file_size
     *
     * @since 1.4
     *
     * @return int max_size in bytes
     *
     */
    static function upload_max_increase_upload( $data ) {
        return get_option('max_file_size') ? get_option('max_file_size') : $data;
    }


}

/**
 * Instance of the class  // Codepopular_WMUFS
 */
add_action('init', array( 'Codepopular_WMUFS', 'init' ));
