<?php
namespace WbsVendors\Dgm\Shengine\Calculators;

use Dgm\Shengine\Interfaces\ICalculator;
use Dgm\Shengine\Interfaces\IPackage;
use Dgm\Shengine\Model\Rate;


class FreeCalculator implements \WbsVendors\Dgm\Shengine\Interfaces\ICalculator
{
    public function calculateRatesFor(\WbsVendors\Dgm\Shengine\Interfaces\IPackage $package)
    {
        return array(new \WbsVendors\Dgm\Shengine\Model\Rate(0));
    }

    public function multipleRatesExpected()
    {
        return false;
    }
}