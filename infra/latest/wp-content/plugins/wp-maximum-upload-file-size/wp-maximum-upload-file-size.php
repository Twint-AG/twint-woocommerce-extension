<?php
/**
* Plugin Name: Wp Maximum Upload File Size
* Description: Wp Maximum Upload File Size will increase upload limit with one click. you can easily increase upload file size according to your need.
* Author: CodePopular
* Author URI: https://codepopular.com
* Plugin URI: https://wordpress.org/plugins/wp-maximum-upload-file-size/
* Version: 1.1.2
* License: GPL2
* Text Domain: wp-maximum-upload-file-size
* Requires at least: 4.0
* Tested up to: 6.6
* Requires PHP: 5.6
* @coypright: -2023 CodePopular (support: info@codepopular.com)
*/

define('WMUFS_PLUGIN_FILE', __FILE__);
define('WMUFS_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('WMUFS_PLUGIN_PATH', trailingslashit(plugin_dir_path(__FILE__)));
define('WMUFS_PLUGIN_URL', trailingslashit(plugins_url('/', __FILE__)));
define('WMUFS_PLUGIN_VERSION', '1.1.2');

/**
 * Increase maximum execution time.
 * Default 600.
 */

$wmufs_get_max_execution_time = get_option('wmufs_maximum_execution_time') != '' ? get_option('wmufs_maximum_execution_time') : ini_get('max_execution_time');
set_time_limit($wmufs_get_max_execution_time);


/**----------------------------------------------------------------*/
/* Include all file
/*-----------------------------------------------------------------*/

/**
 *  Load all required files.
 */

require __DIR__ . '/vendor/autoload.php';

include_once(WMUFS_PLUGIN_PATH . 'inc/class-wmufs-loader.php');

if ( function_exists( 'wmufs_run' ) ) {
  wmufs_run();
}




/**
 * Initialize the plugin tracker
 *
 * @return void
 */
function appsero_init_tracker_wp_maximum_upload_file_size() {

    $client = new Appsero\Client( 'a9151e1a-bc01-4c13-a117-d74263a219d7', 'WP Increase Upload Filesize | Increase Maximum Execution Time', __FILE__ );

    // Active insights
    $client->insights()->init();

}

appsero_init_tracker_wp_maximum_upload_file_size();
