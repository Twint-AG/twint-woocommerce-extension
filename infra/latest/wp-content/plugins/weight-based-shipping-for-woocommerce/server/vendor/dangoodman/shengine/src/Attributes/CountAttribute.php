<?php
namespace WbsVendors\Dgm\Shengine\Attributes;

use Dgm\Shengine\Interfaces\IPackage;


class CountAttribute extends \WbsVendors\Dgm\Shengine\Attributes\AbstractAttribute
{
    public function getValue(\WbsVendors\Dgm\Shengine\Interfaces\IPackage $package)
    {
        return count($package->getItems());
    }
}