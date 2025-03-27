<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Aggregators;

use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IRate;


class MaxAggregator extends ReduceAggregator
{
    protected function reduce(IRate $carry = null, IRate $current)
    {
        if (!isset($carry) || $carry->getCost() < $current->getCost()) {
            $carry = $current;
        }

        return $carry;
    }
}