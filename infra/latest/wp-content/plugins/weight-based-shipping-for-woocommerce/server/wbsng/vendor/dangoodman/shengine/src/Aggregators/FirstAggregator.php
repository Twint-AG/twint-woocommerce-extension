<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Aggregators;

use GzpWbsNgVendors\Dgm\ClassNameAware\ClassNameAware;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IAggregator;


class FirstAggregator extends ClassNameAware implements IAggregator
{
    public function aggregateRates(array $rates)
    {
        return $rates ? reset($rates) : null;
    }
}