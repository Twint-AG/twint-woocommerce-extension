
<?php
// Read plugin header data.
$wmufs_plugin_data = get_plugin_data( WMUFS_PLUGIN_URL );

/**
 * WordPress check upload file size.
 * @return string
 */
function wmufs_wp_minimum_upload_file_size() {
    $wp_size = wp_max_upload_size();
    if ( ! $wp_size ) {
        $wp_size = 'unknown';
        return $wp_size;
    }
    return esc_html( size_format( $wp_size ) );
}

/**
 * Minimum upload size set by hosting provider.
 * @return string
 */
function wmufs_wp_upload_size_by_from_hosting() {
    $ini_size = ini_get( 'upload_max_filesize' );
    if ( ! $ini_size ) {
        $ini_size = 'unknown';
    } elseif ( is_numeric( $ini_size ) ) {
        $ini_size .= ' bytes';
    } else {
        $ini_size .= 'B';
    }

    return $ini_size;
}

/**
 * @param string $from
 * @return int|null
 */
function convertToBytes( string $from ): ?int {
    $units  = [ 'B', 'KB', 'MB', 'GB', 'TB', 'PB' ];
    $number = substr( $from, 0, - 2 );
    $suffix = strtoupper( substr( $from, - 2 ) );

    //B or no suffix
    if ( is_numeric( substr( $suffix, 0, 1 ) ) ) {
        return preg_replace( '/[^\d]/', '', $from );
    }

    $exponent = array_flip( $units )[ $suffix ] ?? null;
    if ( $exponent === null ) { //phpcs:ignore
        return null;
    }

    return (int) $number * ( 1024 ** $exponent );
}


/**
 * Check zipArchive extension enable from hosting.
 * @return bool
 */
function wmufs_check_zip_extension(): bool
{
    $extension = '';
    $extension = in_array( 'zip', get_loaded_extensions() );

    return $extension;
}

/**
 * Check MBstring extension enable from hosting.
 * @return bool
 */
function wmufs_check_mbstring_extension(): bool
{
    $extension = '';
    $extension = in_array( 'mbstring', get_loaded_extensions() );

    return $extension;
}


/**
 * Check dom extension
 * @return bool
 */
function wmufs_check_dom_extension(): bool
{
    $extension = '';
    $extension = in_array( 'dom', get_loaded_extensions() );

    return $extension;
}

// Minimum PHP version.
$wmufs_current_php_version = phpversion();
$wmufs_minimum_php_version = $wmufs_plugin_data['RequiresPHP'] ? $wmufs_plugin_data['RequiresPHP'] : '5.6';
$wmufs_php_version_status         = $wmufs_current_php_version < $wmufs_minimum_php_version ? 0 : 1;

// Minimum WordPress Version.
$wmufs_wp_current_version = get_bloginfo( 'version' );
$wmufs_minimum_wp_version = $wmufs_plugin_data['RequiresWP'] ? $wmufs_plugin_data['RequiresWP'] : '4.4';
$wmufs_wp_version_status         = $wmufs_wp_current_version < $wmufs_minimum_wp_version ? 0 : 1;

// Minimum Woocommerce Version.
if ( class_exists('woocommerce') ) {
    $wmufs_wc_current_version = WC_VERSION;
}else {
    $wmufs_wc_current_version = 'Not Active Woocommerce';
}

$wmufs_minimum_wc_version = isset( $wmufs_plugin_data['WC requires at least'] ) ? $wmufs_plugin_data['WC requires at least'] : '3.2';
$wmufs_wc_status = $wmufs_wc_current_version < $wmufs_minimum_wc_version ? 0 : 1;

// WordPress minimum upload size .
$wmufs_wp_minimum_upload_file_size = '40MB';

// Minimum WordPress upload size..
$wmufs_wp_upload_size_status = convertToBytes( wmufs_wp_minimum_upload_file_size() ) < convertToBytes( $wmufs_wp_minimum_upload_file_size ) ? 0 : 1;

// Minimum upload file size from hosting provider.
$wmufs_wp_upload_size_status_from_hosting = convertToBytes( wmufs_wp_upload_size_by_from_hosting() ) < convertToBytes( $wmufs_wp_minimum_upload_file_size ) ? 0 : 1;

// PHP Limit Time
$wmufs_php_minimum_limit_time = '120';
$wmufs_php_current_limit_time = ini_get('max_execution_time');
$wmufs_php_limit_time_status = $wmufs_php_minimum_limit_time <= $wmufs_php_current_limit_time ? 1 : 0;

// Check if zipArchie extension is enable in hosting.
$wmufs_check_zip_extension_status = wmufs_check_zip_extension() != '1' ? 0 : '1';

// Check MBstring extension from hsoting.
$wmufs_check_mbstring_extension_status = wmufs_check_mbstring_extension() != '1' ? 0 : '1';

// Check dom extension.
$wmufs_check_dom_extension_status = wmufs_check_dom_extension() != '1' ? 0 : '1';

$system_status = array(

    array(
        'title'           => esc_html__( 'PHP Version', 'wp-maximum-upload-file-size' ),
        'version'         => esc_html__('Current Version :  ', 'wp-maximum-upload-file-size') . $wmufs_current_php_version,
        'status'          => $wmufs_php_version_status,
        'success_message' => esc_html__( '- ok', 'wp-maximum-upload-file-size' ),
        'error_message' => esc_html__( 'Recommend Version : ', 'wp-maximum-upload-file-size' ) . $wmufs_minimum_php_version,//phpcs:ignore
    ),

    array(
        'title'           => esc_html__( 'WordPress Version', 'wp-maximum-upload-file-size' ),
        'version'         => $wmufs_wp_current_version,
        'status'          => $wmufs_wp_version_status,
        'success_message' => esc_html__( '- ok', 'wp-maximum-upload-file-size' ),
        'error_message' => esc_html__( 'Recommend : ', 'wp-maximum-upload-file-size') . $wmufs_minimum_wp_version , //phpcs:ignore
    ),

    array(
        'title'           => esc_html__( 'Woocommerce Version', 'wp-maximum-upload-file-size' ),
        'version'         => $wmufs_wc_current_version,
        'status'          => $wmufs_wc_status,
        'success_message' => esc_html__( '- ok', 'wp-maximum-upload-file-size' ),
        'error_message' => esc_html__( 'Recommend : ', 'wp-maximum-upload-file-size') . $wmufs_minimum_wc_version, //phpcs:ignore
    ),

    array(
        'title'           => esc_html__( 'Maximum Upload Limit set by WordPress', 'wp-maximum-upload-file-size' ),
        'version'         => wmufs_wp_minimum_upload_file_size(),
        'status'          => $wmufs_wp_upload_size_status,
        'success_message' => esc_html__( '- ok', 'wp-maximum-upload-file-size' ),
        'error_message' => esc_html__( 'Recommend : ', 'wp-maximum-upload-file-size' ) . $wmufs_wp_minimum_upload_file_size,	//phpcs:ignore
    ),

    array(
        'title'           => esc_html__( 'Maximum Upload Limit Set By Hosting Provider', 'wp-maximum-upload-file-size' ),
        'version'         => wmufs_wp_upload_size_by_from_hosting(),
        'status'          => $wmufs_wp_upload_size_status_from_hosting,
        'success_message' => esc_html__( '- ok', 'wp-maximum-upload-file-size' ),
        'error_message' => esc_html__( 'Recommend :  ', 'wp-maximum-upload-file-size' ) . $wmufs_wp_minimum_upload_file_size, //phpcs:ignore
    ),

    array(
        'title'           => esc_html__( 'PHP Limit Time', 'wp-maximum-upload-file-size' ),
        'version'         => esc_html__('Current Limit Time: ', 'wp-maximum-upload-file-size') . $wmufs_php_current_limit_time,
        'status'          => $wmufs_php_limit_time_status,
        'success_message' => esc_html__( '- Ok', 'wp-maximum-upload-file-size' ),
        'error_message' => esc_html__( 'Recommend : ', 'wp-maximum-upload-file-size' ) . $wmufs_php_minimum_limit_time,	//phpcs:ignore
    ),

    array(
        'title'           => esc_html__( 'zipArchive Extension', 'wp-maximum-upload-file-size' ),
        'version'         => '',
        'status'          => $wmufs_check_zip_extension_status,
        'success_message' => esc_html__( 'Enable', 'wp-maximum-upload-file-size' ),
        'error_message'   => esc_html__( 'Please enable zip extension from hosting.', 'wp-maximum-upload-file-size' ),
    ),

    array(
        'title'           => esc_html__( 'MBString extension', 'wp-maximum-upload-file-size' ),
        'version'         => '',
        'status'          => $wmufs_check_mbstring_extension_status,
        'success_message' => esc_html__( 'Enable', 'wp-maximum-upload-file-size' ),
        'error_message'   => esc_html__( 'Please enable MBString extension from hosting.', 'wp-maximum-upload-file-size' ),
    ),

    array(
        'title'           => esc_html__( 'Dom extension', 'wp-maximum-upload-file-size' ),
        'version'         => '',
        'status'          => $wmufs_check_dom_extension_status,
        'success_message' => esc_html__( 'Enable', 'wp-maximum-upload-file-size' ),
        'error_message'   => esc_html__( 'Dom extension is not enable from hosting.', 'wp-maximum-upload-file-size' ),
    ),
);

