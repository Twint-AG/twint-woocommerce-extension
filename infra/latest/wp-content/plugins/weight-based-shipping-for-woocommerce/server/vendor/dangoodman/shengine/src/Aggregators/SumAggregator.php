<?php
namespace WbsVendors\Dgm\Shengine\Aggregators;

use Dgm\Shengine\Interfaces\IRate;
use Dgm\Shengine\Processing\RateRegister;


class SumAggregator extends \WbsVendors\Dgm\Shengine\Aggregators\ReduceAggregator
{
    protected function reduce(\WbsVendors\Dgm\Shengine\Interfaces\IRate $carry = null, \WbsVendors\Dgm\Shengine\Interfaces\IRate $current)
    {
        if (!isset($carry)) {
            $carry = new \WbsVendors\Dgm\Shengine\Processing\RateRegister();
        }

        $carry->add($current);

        return $carry;
    }
}