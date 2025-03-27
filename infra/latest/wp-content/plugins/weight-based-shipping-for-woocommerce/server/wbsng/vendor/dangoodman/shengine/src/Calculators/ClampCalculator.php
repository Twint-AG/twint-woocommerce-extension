<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Calculators;

use GzpWbsNgVendors\Dgm\Arrays\Arrays;
use GzpWbsNgVendors\Dgm\Range\Range;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\ICalculator;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IPackage;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IRate;
use GzpWbsNgVendors\Dgm\Shengine\Model\Rate;


class ClampCalculator implements ICalculator
{
    public function __construct(ICalculator $calculator, Range $range)
    {
        $this->range = $range;
        $this->calculator = $calculator;
    }

    public function calculateRatesFor(IPackage $package)
    {
        $range = $this->range;
        return Arrays::map($this->calculator->calculateRatesFor($package), function(IRate $rate) use($range) {
            return new Rate($range->clamp($rate->getCost()), $rate->getTitle());
        });
    }

    public function multipleRatesExpected()
    {
        return $this->calculator->multipleRatesExpected();
    }

    private $calculator;
    private $range;
}