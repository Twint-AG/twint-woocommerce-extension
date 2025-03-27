<?php
namespace WbsVendors\Dgm\Shengine\Aggregators;

use Dgm\ClassNameAware\ClassNameAware;
use Dgm\Shengine\Interfaces\IAggregator;


class LastAggregator extends \WbsVendors\Dgm\ClassNameAware\ClassNameAware implements \WbsVendors\Dgm\Shengine\Interfaces\IAggregator
{
    public function aggregateRates(array $rates)
    {
        return $rates ? end($rates) : null;
    }
}