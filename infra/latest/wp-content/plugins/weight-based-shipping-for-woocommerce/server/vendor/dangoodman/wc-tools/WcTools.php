<?php
namespace WbsVendors\Dgm\WcTools;


class WcTools
{
    /**
     * @return bool
     */
    public static function isActive()
    {
        if (!function_exists('is_plugin_active')) {
            include_once(ABSPATH.'wp-admin/includes/plugin.php');
        }
        if (is_plugin_active('woocommerce/woocommerce.php')) {
            return true;
        }

        return false;
    }

    public static function purgeShippingCache()
    {
        if (!class_exists('WC_Cache_Helper') || !method_exists('WC_Cache_Helper', 'get_transient_version')) {

            global $wpdb;

            /** @noinspection SqlDialectInspection */
            /** @noinspection SqlNoDataSourceInspection */
            $transients = $wpdb->get_col("
                SELECT SUBSTR(option_name, LENGTH('_transient_') + 1)
                FROM `{$wpdb->options}`
                WHERE option_name LIKE '_transient_wc_ship_%'
            ");

            foreach ($transients as $transient) {
                delete_transient($transient);
            }

            return;
        }

        \WC_Cache_Helper::get_transient_version('shipping', true);
    }

    public static function yesNo2Bool($value)
    {
        return is_bool($value) ? $value : $value === 'yes';
    }

    public static function bool2YesNo($value)
    {
        return (bool)$value ? 'yes' : 'no';
    }
}