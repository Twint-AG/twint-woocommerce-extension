<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Attributes;

use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IPackage;


class DestinationAttribute extends AbstractAttribute
{
    public function getValue(IPackage $package)
    {
        return $package->getDestination();
    }
}