<?php
namespace WbsVendors\Dgm\Shengine\Conditions\Common\Compare;


class LessCondition extends \WbsVendors\Dgm\Shengine\Conditions\Common\Compare\CompareCondition
{
    public function isSatisfiedBy($value)
    {
        return $this->comparator->less($value, $this->compareWith);
    }
}