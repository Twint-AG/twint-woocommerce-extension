<?php
namespace WbsVendors\Dgm\Shengine\Aggregators;

use Dgm\Shengine\Interfaces\IRate;


class MinAggregator extends \WbsVendors\Dgm\Shengine\Aggregators\ReduceAggregator
{
    protected function reduce(\WbsVendors\Dgm\Shengine\Interfaces\IRate $carry = null, \WbsVendors\Dgm\Shengine\Interfaces\IRate $current)
    {
        if (!isset($carry) || $carry->getCost() > $current->getCost()) {
            $carry = $current;
        }

        return $carry;
    }
}