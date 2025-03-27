<?php
namespace WbsVendors\Dgm\Shengine\Calculators;

use Dgm\Shengine\Interfaces\IAggregator;
use Dgm\Shengine\Interfaces\ICalculator;
use Dgm\Shengine\Interfaces\IPackage;


class AggregatedCalculator implements \WbsVendors\Dgm\Shengine\Interfaces\ICalculator
{
    public function __construct(\WbsVendors\Dgm\Shengine\Interfaces\ICalculator $calculator, \WbsVendors\Dgm\Shengine\Interfaces\IAggregator $aggregator = null)
    {
        $this->calculator = $calculator;
        $this->aggregator = $aggregator;
    }

    public function calculateRatesFor(\WbsVendors\Dgm\Shengine\Interfaces\IPackage $package)
    {
        $rates = $this->calculator->calculateRatesFor($package);

        if (isset($this->aggregator)) {
            $rate = $this->aggregator->aggregateRates($rates);
            $rates = isset($rate) ? array($rate) : array();
        }

        return $rates;
    }

    public function multipleRatesExpected()
    {
        return !isset($this->aggregator) && $this->calculator->multipleRatesExpected();
    }

    private $calculator;
    private $aggregator;
}