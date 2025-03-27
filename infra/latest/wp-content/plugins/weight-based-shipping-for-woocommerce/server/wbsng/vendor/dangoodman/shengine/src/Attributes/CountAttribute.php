<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Attributes;

use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IPackage;


class CountAttribute extends AbstractAttribute
{
    public function getValue(IPackage $package)
    {
        return count($package->getItems());
    }
}