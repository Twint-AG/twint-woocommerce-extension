<?php
namespace WbsVendors\Dgm\Shengine\Migrations\Storage;

use Dgm\Shengine\Migrations\Interfaces\Storage\IStorage;
use wpdb;


class WordpressOptions implements \WbsVendors\Dgm\Shengine\Migrations\Interfaces\Storage\IStorage
{
    public function __construct(wpdb $wpdb)
    {
        $this->wpdb = $wpdb;
    }

    public function bind($key, $default = null)
    {
        return new \WbsVendors\Dgm\Shengine\Migrations\Storage\StorageRecord($this, $key, $default);
    }

    public function get($key, $default = null)
    {
        return get_option($key, $default);
    }

    public function set($key, $value, $autoload = null)
    {
        update_option($key, $value, $autoload);
    }

    public function findKeysLike($sqlLikePattern)
    {
        $query = $this->wpdb->prepare("
            SELECT `option_name` 
            FROM {$this->wpdb->options} 
            WHERE `option_name` LIKE %s",
        $sqlLikePattern);

        $keys = $this->wpdb->get_col($query, 0);

        return $keys;
    }

    public function escapeForLikePattern($string)
    {
        return $this->wpdb->esc_like($string);
    }

    private $wpdb;
}