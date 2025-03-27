<?php
namespace WbsVendors\Dgm\Shengine\Migrations\Interfaces\Storage;


interface IStorageAccess
{
    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function get($key, $default = false);

    /**
     * @param string $key
     * @param mixed $value
     * @param bool|null $autoload
     */
    function set($key, $value, $autoload = null);
}