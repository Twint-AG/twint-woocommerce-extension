<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Grouping;

use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IGrouping;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IItem;


class ByItemGrouping implements IGrouping
{
    public static function instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getPackageIds(IItem $item)
    {
        return [spl_object_hash($item)];
    }

    public function multiplePackagesExpected()
    {
        return true;
    }

    /** @var self */
    private static $instance;

    private function __construct()
    {
    }
}
