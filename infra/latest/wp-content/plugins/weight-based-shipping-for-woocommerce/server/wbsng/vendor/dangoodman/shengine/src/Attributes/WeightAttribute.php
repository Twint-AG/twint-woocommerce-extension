<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Attributes;

use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IPackage;


class WeightAttribute extends AbstractAttribute
{
    public function getValue(IPackage $package)
    {
        return $package->getWeight();
    }
}