<?php
namespace WbsVendors\Dgm\Shengine\Migrations\Storage;

use Dgm\Shengine\Migrations\Interfaces\Storage\IStorageAccess;
use Dgm\Shengine\Migrations\Interfaces\Storage\IStorageRecord;


class StorageRecord implements \WbsVendors\Dgm\Shengine\Migrations\Interfaces\Storage\IStorageRecord
{
    public function __construct(\WbsVendors\Dgm\Shengine\Migrations\Interfaces\Storage\IStorageAccess $parent, $key, $default = null)
    {
        $this->parent = $parent;
        $this->key = $key;
        $this->default = $default;
    }

    public function get()
    {
        return $this->parent->get($this->key, $this->default);
    }

    public function set($value)
    {
        $this->parent->set($this->key, $value);
    }

    private $parent;
    private $key;
    private $default;
}