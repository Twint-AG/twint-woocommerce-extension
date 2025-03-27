<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Calculators;

use GzpWbsNgVendors\Dgm\Shengine\Interfaces\ICalculator;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IPackage;
use GzpWbsNgVendors\Dgm\Shengine\Model\Rate;


class ConstantCalculator implements ICalculator
{
    public function __construct($cost)
    {
        $this->cost = $cost;
    }

    public function calculateRatesFor(IPackage $package)
    {
        return array(new Rate($this->cost));
    }

    public function multipleRatesExpected()
    {
        return false;
    }

    private $cost;
}
