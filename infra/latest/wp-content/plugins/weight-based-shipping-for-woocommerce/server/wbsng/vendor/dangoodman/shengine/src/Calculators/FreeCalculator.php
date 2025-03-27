<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Calculators;

use GzpWbsNgVendors\Dgm\Shengine\Interfaces\ICalculator;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IPackage;
use GzpWbsNgVendors\Dgm\Shengine\Model\Rate;


class FreeCalculator implements ICalculator
{
    public function calculateRatesFor(IPackage $package)
    {
        return array(new Rate(0));
    }

    public function multipleRatesExpected()
    {
        return false;
    }
}