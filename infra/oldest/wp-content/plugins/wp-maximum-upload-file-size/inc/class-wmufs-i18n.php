<?php

class Wmufs_I18n
{
    /**
     * Intialize text domain.
     *
     * @since 1.0.7
     */
    function __construct(){
        add_action( 'plugins_loaded', [ $this, 'load_plugin_textdomain' ] );
    }

    /**
     * Load the plugin text domain for translation.
     *
     * @since 1.0.7
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'wp-maximum-upload-file-size',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );

    }
}

new Wmufs_I18n();
