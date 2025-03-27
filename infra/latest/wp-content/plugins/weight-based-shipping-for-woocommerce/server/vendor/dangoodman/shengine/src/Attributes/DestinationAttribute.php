<?php
namespace WbsVendors\Dgm\Shengine\Attributes;

use Dgm\Shengine\Interfaces\IPackage;


class DestinationAttribute extends \WbsVendors\Dgm\Shengine\Attributes\AbstractAttribute
{
    public function getValue(\WbsVendors\Dgm\Shengine\Interfaces\IPackage $package)
    {
        return $package->getDestination();
    }
}