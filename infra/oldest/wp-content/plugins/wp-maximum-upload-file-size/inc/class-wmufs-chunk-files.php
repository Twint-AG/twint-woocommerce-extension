<?php
class WMUFS_File_Chunk{


    private static $instance = null;


    protected $max_upload_size;

    /**
     * Make instance of the admin class.
     */
    public static function get_instance() {
        if ( ! self::$instance)
            self::$instance = new self();
        return self::$instance;
    }


    /**
     * Load all action and filters.
     * @return void
     */
    public function init(){
        $this->max_upload_size = wp_max_upload_size();
        add_action( 'wp_ajax_wmufs_chunker', array( $this, 'wmufs_ajax_chunk_receiver' ) );
        add_filter( 'plupload_init', array( $this, 'wmufs_filter_plupload_settings' ) );
        add_filter( 'plupload_default_settings', array( $this, 'wmufs_filter_plupload_settings' ) );
        add_filter( 'plupload_default_params', array( $this, 'wmufs_filter_plupload_params' ) );
        add_filter( 'upload_post_params', array( $this, 'wmufs_filter_plupload_params' ) );
    }





    /**
     * @param $plupload_params
     * @return mixed
     */
    public function wmufs_filter_plupload_params( $plupload_params ) {

        $plupload_params['action'] = 'wmufs_chunker';

        return $plupload_params;

    }


    /**
     * AJAX chunk receiver.
     *
     * Ajax callback for plupload to handle chunked uploads.
     * Based on code by Davit Barbakadze
     * https://gist.github.com/jayarjo/5846636
     *
     * Mirrors /wp-admin/async-upload.php
     *
     */
    public function wmufs_ajax_chunk_receiver() {

        /** Check that we have an upload and there are no errors. */
        if ( empty( $_FILES ) || $_FILES['async-upload']['error'] ) {
            /** Failed to move uploaded file. */
            die();
        }

        /** Authenticate user. */
        if ( ! is_user_logged_in() || ! current_user_can( 'upload_files' ) ) {
            wp_die( __( 'Sorry, you are not allowed to upload files.' ) );
        }
        check_admin_referer( 'media-form' );

        /** Check and get file chunks. */
        $chunk  = isset( $_REQUEST['chunk'] ) ? intval( $_REQUEST['chunk'] ) : 0; //zero index
        $current_part = $chunk + 1;
        $chunks = isset( $_REQUEST['chunks'] ) ? intval( $_REQUEST['chunks'] ) : 0;

        /** Get file name and path + name. */
        $fileName = isset( $_REQUEST['name'] ) ? $_REQUEST['name'] : $_FILES['async-upload']['name'];


        $wmufs_temp_dir = apply_filters( 'wmufs_temp_dir', WP_CONTENT_DIR . '/wmufs-temp' );

        //only run on first chunk
        if ( $chunk === 0 ) {
            // Create temp directory if it doesn't exist
            if ( ! @is_dir( $wmufs_temp_dir ) ) {
                wp_mkdir_p( $wmufs_temp_dir );
            }

            // Protect temp directory from browsing.
            $index_pathname = $wmufs_temp_dir . '/index.php';
            if ( ! file_exists( $index_pathname ) ) {
                $file = fopen( $index_pathname, 'w' );
                if ( false !== $file ) {
                    fwrite( $file, "<?php\n// Silence is golden.\n" );
                    fclose( $file );
                }
            }

            //scan temp dir for files older than 24 hours and delete them.
            $files = glob( $wmufs_temp_dir . '/*.part' );
            if ( is_array( $files ) ) {
                foreach ( $files as $file ) {
                    if ( @filemtime( $file ) < time() - DAY_IN_SECONDS ) {
                        @unlink( $file );
                    }
                }
            }
        }

        $filePath = sprintf( '%s/%d-%s.part', $wmufs_temp_dir, get_current_blog_id(), sha1( $fileName ) );

        //debugging
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            $size = file_exists( $filePath ) ? size_format( filesize( $filePath ), 3 ) : '0 B';
            error_log( "WMUFS: Processing \"$fileName\" part $current_part of $chunks as $filePath. $size processed so far." );
        }

        $wmufs_max_upload_size = $this->get_upload_limit();
        if ( file_exists( $filePath ) && filesize( $filePath ) + filesize( $_FILES['async-upload']['tmp_name'] ) > $wmufs_max_upload_size ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( "WMUFS: File size limit exceeded." );
            }

            if ( ! $chunks || $chunk == $chunks - 1 ) {
                @unlink( $filePath );

                if ( ! isset( $_REQUEST['short'] ) || ! isset( $_REQUEST['type'] ) ) {
                    echo wp_json_encode( array(
                        'success' => false,
                        'data'    => array(
                            'message'  => __( 'The file size has exceeded the maximum file size setting.', 'tuxedo-big-file-uploads' ),
                            'filename' => $fileName,
                        ),
                    ) );
                    wp_die();
                } else {
                    status_header( 202 );
                    printf(
                        '<div class="error-div error">%s <strong>%s</strong><br />%s</div>',
                        sprintf(
                            '<button type="button" class="dismiss button-link" onclick="jQuery(this).parents(\'div.media-item\').slideUp(200, function(){jQuery(this).remove();});">%s</button>',
                            __( 'Dismiss' )
                        ),
                        sprintf(
                        /* translators: %s: Name of the file that failed to upload. */
                            __( '&#8220;%s&#8221; has failed to upload.' ),
                            esc_html( $fileName )
                        ),
                        __( 'The file size has exceeded the maximum file size setting.', 'tuxedo-big-file-uploads' )
                    );
                    exit;
                }
}

            die();
        }

        /** Open temp file. */
        if ( $chunk == 0 ) {
            $out = @fopen( $filePath, 'wb');
        } elseif ( is_writable( $filePath ) ) { //
            $out = @fopen( $filePath, 'ab' );
        } else {
            $out = false;
        }

        if ( $out ) {

            /** Read binary input stream and append it to temp file. */
            $in = @fopen( $_FILES['async-upload']['tmp_name'], 'rb' );

            if ( $in ) {
                while ( $buff = fread( $in, 4096 ) ) {
                    fwrite( $out, $buff );
                }
            } else {
                /** Failed to open input stream. */
                /** Attempt to clean up unfinished output. */
                @fclose( $out );
                @unlink( $filePath );
                error_log( "WMUFS: Error reading uploaded part $current_part of $chunks." );

                if ( ! isset( $_REQUEST['short'] ) || ! isset( $_REQUEST['type'] ) ) {
                    echo wp_json_encode(
                        array(
                            'success' => false,
                            'data'    => array(
                                'message'  => sprintf( __( 'There was an error reading uploaded part %1$d of %2$d.', 'tuxedo-big-file-uploads' ), $current_part, $chunks ),
                                'filename' => esc_html( $fileName ),
                            ),
                        )
                    );
                    wp_die();
                } else {
                    status_header( 202 );
                    printf(
                        '<div class="error-div error">%s <strong>%s</strong><br />%s</div>',
                        sprintf(
                            '<button type="button" class="dismiss button-link" onclick="jQuery(this).parents(\'div.media-item\').slideUp(200, function(){jQuery(this).remove();});">%s</button>',
                            __( 'Dismiss' )
                        ),
                        sprintf(
                        /* translators: %s: Name of the file that failed to upload. */
                            __( '&#8220;%s&#8221; has failed to upload.' ),
                            esc_html( $fileName )
                        ),
                        sprintf( __( 'There was an error reading uploaded part %1$d of %2$d.', 'tuxedo-big-file-uploads' ), $current_part, $chunks )
                    );
                    exit;
                }
            }

            @fclose( $in );
            @fclose( $out );
            @unlink( $_FILES['async-upload']['tmp_name'] );
        } else {
            /** Failed to open output stream. */
            error_log( "BFU: Failed to open output stream $filePath to write part $current_part of $chunks." );

            if ( ! isset( $_REQUEST['short'] ) || ! isset( $_REQUEST['type'] ) ) {
                echo wp_json_encode(
                    array(
                        'success' => false,
                        'data'    => array(
                            'message'  => sprintf( __( 'There was an error opening the temp file %s for writing. Available temp directory space may be exceeded or the temp file was cleaned up before the upload completed.', 'tuxedo-big-file-uploads' ), esc_html( $filePath ) ),
                            'filename' => esc_html( $fileName ),
                        ),
                    )
                );
                wp_die();
            } else {
                status_header( 202 );
                printf(
                    '<div class="error-div error">%s <strong>%s</strong><br />%s</div>',
                    sprintf(
                        '<button type="button" class="dismiss button-link" onclick="jQuery(this).parents(\'div.media-item\').slideUp(200, function(){jQuery(this).remove();});">%s</button>',
                        __( 'Dismiss' )
                    ),
                    sprintf(
                    /* translators: %s: Name of the file that failed to upload. */
                        __( '&#8220;%s&#8221; has failed to upload.' ),
                        esc_html( $fileName )
                    ),
                    sprintf( __( 'There was an error opening the temp file %s for writing. Available temp directory space may be exceeded or the temp file was cleaned up before the upload completed.', 'tuxedo-big-file-uploads' ), esc_html( $filePath ) )
                );
                exit;
            }
        }

        /** Check if file has finished uploading all parts. */
        if ( ! $chunks || $chunk == $chunks - 1 ) {

            //debugging
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                $size = file_exists( $filePath ) ? size_format( filesize( $filePath ), 3 ) : '0 B';
                error_log( "BFU: Completing \"$fileName\" upload with a $size final size." );
            }

            /** Recreate upload in $_FILES global and pass off to WordPress. */
            $_FILES['async-upload']['tmp_name'] = $filePath;
            $_FILES['async-upload']['name'] = $fileName;
            $_FILES['async-upload']['size'] = filesize( $_FILES['async-upload']['tmp_name'] );
            $wp_filetype = wp_check_filetype_and_ext( $_FILES['async-upload']['tmp_name'], $_FILES['async-upload']['name'] );
            $_FILES['async-upload']['type'] = $wp_filetype['type'];

            header( 'Content-Type: text/plain; charset=' . get_option( 'blog_charset' ) );

            if ( ! isset( $_REQUEST['short'] ) || ! isset( $_REQUEST['type'] ) ) { //ajax like media uploader in modal

                // Compatibility with Easy Digital Downloads plugin.
                if ( function_exists( 'edd_change_downloads_upload_dir' ) ) {
                    global $pagenow;
                    $pagenow = 'async-upload.php';
                    edd_change_downloads_upload_dir();
                }

                send_nosniff_header();
                nocache_headers();

                $this->wp_ajax_upload_attachment();
                die( '0' );

            } else { //non-ajax like add new media page
                $post_id = 0;
                if ( isset( $_REQUEST['post_id'] ) ) {
                    $post_id = absint( $_REQUEST['post_id'] );
                    if ( ! get_post( $post_id ) || ! current_user_can( 'edit_post', $post_id ) )
                        $post_id = 0;
                }

                $id = media_handle_upload( 'async-upload', $post_id, [], [
					'action' => 'wp_handle_sideload',
					'test_form' => false,
				] );
                if ( is_wp_error( $id ) ) {
                    printf(
                        '<div class="error-div error">%s <strong>%s</strong><br />%s</div>',
                        sprintf(
                            '<button type="button" class="dismiss button-link" onclick="jQuery(this).parents(\'div.media-item\').slideUp(200, function(){jQuery(this).remove();});">%s</button>',
                            __( 'Dismiss' )
                        ),
                        sprintf(
                        /* translators: %s: Name of the file that failed to upload. */
                            __( '&#8220;%s&#8221; has failed to upload.' ),
                            esc_html( $_FILES['async-upload']['name'] )
                        ),
                        esc_html( $id->get_error_message() )
                    );
                    exit;
                }

                if ( $_REQUEST['short'] ) {
                    // Short form response - attachment ID only.
                    echo $id;
                } else {
                    // Long form response - big chunk of HTML.
                    $type = $_REQUEST['type'];

                    /**
                     * Filters the returned ID of an uploaded attachment.
                     *
                     * The dynamic portion of the hook name, `$type`, refers to the attachment type.
                     *
                     * Possible hook names include:
                     *
                     *  - `async_upload_audio`
                     *  - `async_upload_file`
                     *  - `async_upload_image`
                     *  - `async_upload_video`
                     *
                     * @since 1.1.0
                     *
                     * @param int $id Uploaded attachment ID.
                     */
                    echo apply_filters( "async_upload_{$type}", $id );
                }
}
}

        die();
    }

    /**
     * Return the maximum upload limit in bytes for the current user.
     *
     * @since 1.1.0
     *
     * @return integer
     */
    function get_upload_limit() {
        $max_size = (int) get_option('max_file_size');
        if ( ! $max_size ) {
            $max_size = wp_max_upload_size();
        }
        return $max_size;
    }


    /**
     * Copied from wp-admin/includes/ajax-actions.php because we have to override the args for
     * the media_handle_upload function. As of WP 6.0.1
     */
    function wp_ajax_upload_attachment() {
        check_ajax_referer( 'media-form' );
        /*
         * This function does not use wp_send_json_success() / wp_send_json_error()
         * as the html4 Plupload handler requires a text/html content-type for older IE.
         * See https://core.trac.wordpress.org/ticket/31037
         */

        if ( ! current_user_can( 'upload_files' ) ) {
            echo wp_json_encode(
                array(
                    'success' => false,
                    'data'    => array(
                        'message'  => __( 'Sorry, you are not allowed to upload files.' ),
                        'filename' => esc_html( $_FILES['async-upload']['name'] ),
                    ),
                )
            );

            wp_die();
        }

        if ( isset( $_REQUEST['post_id'] ) ) {
            $post_id = $_REQUEST['post_id'];

            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                echo wp_json_encode(
                    array(
                        'success' => false,
                        'data'    => array(
                            'message'  => __( 'Sorry, you are not allowed to attach files to this post.' ),
                            'filename' => esc_html( $_FILES['async-upload']['name'] ),
                        ),
                    )
                );

                wp_die();
            }
        } else {
            $post_id = null;
        }

        $post_data = ! empty( $_REQUEST['post_data'] ) ? _wp_get_allowed_postdata( _wp_translate_postdata( false, (array) $_REQUEST['post_data'] ) ) : array();

        if ( is_wp_error( $post_data ) ) {
            wp_die( $post_data->get_error_message() );
        }

        // If the context is custom header or background, make sure the uploaded file is an image.
        if ( isset( $post_data['context'] ) && in_array( $post_data['context'], array( 'custom-header', 'custom-background' ), true ) ) {
            $wp_filetype = wp_check_filetype_and_ext( $_FILES['async-upload']['tmp_name'], $_FILES['async-upload']['name'] );

            if ( ! wp_match_mime_types( 'image', $wp_filetype['type'] ) ) {
                echo wp_json_encode(
                    array(
                        'success' => false,
                        'data'    => array(
                            'message'  => __( 'The uploaded file is not a valid image. Please try again.' ),
                            'filename' => esc_html( $_FILES['async-upload']['name'] ),
                        ),
                    )
                );

                wp_die();
            }
        }

        //this is the modded function from wp-admin/includes/ajax-actions.php
        $attachment_id = media_handle_upload( 'async-upload', $post_id, $post_data, [
			'action' => 'wp_handle_sideload',
			'test_form' => false,
		] );

        if ( is_wp_error( $attachment_id ) ) {
            echo wp_json_encode(
                array(
                    'success' => false,
                    'data'    => array(
                        'message'  => $attachment_id->get_error_message(),
                        'filename' => esc_html( $_FILES['async-upload']['name'] ),
                    ),
                )
            );

            wp_die();
        }

        if ( isset( $post_data['context'] ) && isset( $post_data['theme'] ) ) {
            if ( 'custom-background' === $post_data['context'] ) {
                update_post_meta( $attachment_id, '_wp_attachment_is_custom_background', $post_data['theme'] );
            }

            if ( 'custom-header' === $post_data['context'] ) {
                update_post_meta( $attachment_id, '_wp_attachment_is_custom_header', $post_data['theme'] );
            }
        }

        $attachment = wp_prepare_attachment_for_js( $attachment_id );
        if ( ! $attachment ) {
            wp_die();
        }

        echo wp_json_encode(
            array(
                'success' => true,
                'data'    => $attachment,
            )
        );

        wp_die();
    }


    /**
     * Filter plupload settings.
     *
     * @since 1.1.0
     */
    public function wmufs_filter_plupload_settings( $plupload_settings ) {

        $max_chunk = ( MB_IN_BYTES * 20 ); //20MB max chunk size (to avoid timeouts)
        if ( $max_chunk > $this->max_upload_size ) {
            $default_chunk = ( $this->max_upload_size * 0.8 ) / KB_IN_BYTES;
        } else {
            $default_chunk = $max_chunk / KB_IN_BYTES;
        }
        //define( 'WMUFS_FILE_UPLOADS_CHUNK_SIZE_KB', 512 );//TODO remove
        if ( ! defined( 'WMUFS_FILE_UPLOADS_CHUNK_SIZE_KB' ) ) {
            define( 'WMUFS_FILE_UPLOADS_CHUNK_SIZE_KB', $default_chunk );
        }

        if ( ! defined( 'WMUFS_FILE_UPLOADS_RETRIES' ) ) {
            define( 'WMUFS_FILE_UPLOADS_RETRIES', 1 );
        }

        $plupload_settings['url']                      = admin_url( 'admin-ajax.php' );
        $plupload_settings['filters']['max_file_size'] = $this->get_upload_limit( '' ) . 'b';
        $plupload_settings['chunk_size']               = WMUFS_FILE_UPLOADS_CHUNK_SIZE_KB . 'kb';
        $plupload_settings['max_retries']              = WMUFS_FILE_UPLOADS_RETRIES;

        return $plupload_settings;
    }

}

WMUFS_File_Chunk::get_instance()->init();
