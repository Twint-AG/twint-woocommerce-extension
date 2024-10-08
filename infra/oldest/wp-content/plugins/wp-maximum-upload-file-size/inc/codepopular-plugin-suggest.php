<?php

/**
 * Class CodePopular_Plugin_Suggest
 */
class CodePopular_Plugin_Suggest{

    private static $instance = null;

    /**
     * @return CodePopular_Plugin_Suggest|null
     */
    public static function get_instance() {
        if ( ! self::$instance )
            self::$instance = new self();
        return self::$instance;
    }

    /**
     * Initialize global hooks.
     */
    public function init(){
        add_filter('install_plugins_table_api_args_featured', array( __CLASS__, 'featured_plugins_tab' ));
    }

    /**
     * helper function for adding plugins to fav list.
     * @param $args
     * @return mixed
     */
    static function featured_plugins_tab( $args ) {
        add_filter('plugins_api_result', array( __CLASS__, 'plugins_api_result' ), 10, 3);

        return $args;
    }

    /**
     * add our plugins to recommended list
     * @param $res
     * @param $action
     * @param $args
     * @return mixed
     */
    static function plugins_api_result( $res, $action, $args ) {
        remove_filter('plugins_api_result', array( __CLASS__, 'plugins_api_result' ), 10, 3);
        $res = self::add_plugin_favs('unlimited-theme-addons', $res);
        return $res;
    } // plugins_api_result


    /**
     * add single plugin to list of favs
     * @param $plugin_slug
     * @param $res
     * @return mixed
     */
    static function add_plugin_favs( $plugin_slug, $res ) {
        if ( ! empty( $res->plugins ) && is_array( $res->plugins ) ) {
            foreach ( $res->plugins as $plugin ) {
                if ( is_object($plugin) && ! empty($plugin->slug) && $plugin->slug == $plugin_slug ) {
                    return $res;
                }
            }
        }

        if ( $plugin_info = get_transient('wf-plugin-info-' . $plugin_slug) ) {
            array_unshift($res->plugins, $plugin_info);
        } else {
            $plugin_info = plugins_api('plugin_information', array(
                'slug'   => $plugin_slug,
                'is_ssl' => is_ssl(),
                'fields' => array(
                    'banners'           => true,
                    'reviews'           => true,
                    'downloaded'        => true,
                    'active_installs'   => true,
                    'icons'             => true,
                    'short_description' => true,
                ),
            ));
            if ( ! is_wp_error($plugin_info) ) {
                $res->plugins[] = $plugin_info;
                set_transient('wf-plugin-info-' . $plugin_slug, $plugin_info, DAY_IN_SECONDS * 7);
            }
        }

        return $res;
    }

}

CodePopular_Plugin_Suggest::get_instance()->init();


