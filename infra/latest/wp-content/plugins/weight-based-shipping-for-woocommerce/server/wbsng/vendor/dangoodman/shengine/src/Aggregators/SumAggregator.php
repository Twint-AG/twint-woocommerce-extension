<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Aggregators;

use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IRate;
use GzpWbsNgVendors\Dgm\Shengine\Processing\RateRegister;


class SumAggregator extends ReduceAggregator
{
    protected function reduce(IRate $carry = null, IRate $current)
    {
        if (!isset($carry)) {
            $carry = new RateRegister();
        }

        $carry->add($current);

        return $carry;
    }
}