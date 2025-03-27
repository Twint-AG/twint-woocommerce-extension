<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Grouping;

use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IGrouping;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IItem;
use Dgm\Shengine\Interfaces\IPackage;


class NoopGrouping implements IGrouping
{
    public function getPackageIds(IItem $item)
    {
        return ['noop'];
    }

    public function multiplePackagesExpected()
    {
        return false;
    }
}
