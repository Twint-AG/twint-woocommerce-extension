<?php
namespace WbsVendors\Dgm\Shengine\Calculators;

use Dgm\Arrays\Arrays;
use Dgm\Range\Range;
use Dgm\Shengine\Interfaces\ICalculator;
use Dgm\Shengine\Interfaces\IPackage;
use Dgm\Shengine\Interfaces\IRate;
use Dgm\Shengine\Model\Rate;


class ClampCalculator implements \WbsVendors\Dgm\Shengine\Interfaces\ICalculator
{
    public function __construct(\WbsVendors\Dgm\Shengine\Interfaces\ICalculator $calculator, \WbsVendors\Dgm\Range\Range $range)
    {
        $this->range = $range;
        $this->calculator = $calculator;
    }

    public function calculateRatesFor(\WbsVendors\Dgm\Shengine\Interfaces\IPackage $package)
    {
        $range = $this->range;
        return \WbsVendors\Dgm\Arrays\Arrays::map($this->calculator->calculateRatesFor($package), function(\WbsVendors\Dgm\Shengine\Interfaces\IRate $rate) use($range) {
            return new \WbsVendors\Dgm\Shengine\Model\Rate($range->clamp($rate->getCost()), $rate->getTitle());
        });
    }

    public function multipleRatesExpected()
    {
        return $this->calculator->multipleRatesExpected();
    }

    private $calculator;
    private $range;
}