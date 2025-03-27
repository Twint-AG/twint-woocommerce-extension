<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Attributes;

use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IPackage;


class CouponsAttribute extends AbstractAttribute
{
    public function getValue(IPackage $package)
    {
        return $package->getCoupons();
    }
}