<?php
namespace WbsVendors\Dgm\Shengine\Aggregators;

use Dgm\ClassNameAware\ClassNameAware;
use Dgm\Shengine\Interfaces\IAggregator;
use Dgm\Shengine\Model\Rate;


class AverageAggregator extends \WbsVendors\Dgm\ClassNameAware\ClassNameAware implements \WbsVendors\Dgm\Shengine\Interfaces\IAggregator
{
    public function __construct()
    {
        $this->sum = new \WbsVendors\Dgm\Shengine\Aggregators\SumAggregator();
    }

    public function aggregateRates(array $rates)
    {
        $result = $this->sum->aggregateRates($rates);
        if (isset($result)) {
            $result = new \WbsVendors\Dgm\Shengine\Model\Rate($result->getCost() / count($rates), $result->getTitle());
        }

        return $result;
    }

    private $sum;
}