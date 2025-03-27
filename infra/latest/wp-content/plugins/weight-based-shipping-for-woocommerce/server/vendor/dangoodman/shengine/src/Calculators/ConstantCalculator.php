<?php
namespace WbsVendors\Dgm\Shengine\Calculators;

use Dgm\Shengine\Interfaces\ICalculator;
use Dgm\Shengine\Interfaces\IPackage;
use Dgm\Shengine\Model\Rate;


class ConstantCalculator implements \WbsVendors\Dgm\Shengine\Interfaces\ICalculator
{
    public function __construct($cost)
    {
        $this->cost = $cost;
    }

    public function calculateRatesFor(\WbsVendors\Dgm\Shengine\Interfaces\IPackage $package)
    {
        return array(new \WbsVendors\Dgm\Shengine\Model\Rate($this->cost));
    }

    public function multipleRatesExpected()
    {
        return false;
    }

    private $cost;
}
