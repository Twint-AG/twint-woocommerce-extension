<?php
namespace WbsVendors\Dgm\Shengine\Attributes;

use Dgm\Shengine\Interfaces\IPackage;


abstract class SumAttribute extends \WbsVendors\Dgm\Shengine\Attributes\MapAttribute
{
    public function getValue(\WbsVendors\Dgm\Shengine\Interfaces\IPackage $package)
    {
        return array_sum(parent::getValue($package));
    }
}