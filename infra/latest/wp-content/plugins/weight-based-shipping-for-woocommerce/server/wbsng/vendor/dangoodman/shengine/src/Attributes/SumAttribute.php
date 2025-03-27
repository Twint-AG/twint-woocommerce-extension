<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Attributes;

use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IPackage;


abstract class SumAttribute extends MapAttribute
{
    public function getValue(IPackage $package)
    {
        return array_sum(parent::getValue($package));
    }
}