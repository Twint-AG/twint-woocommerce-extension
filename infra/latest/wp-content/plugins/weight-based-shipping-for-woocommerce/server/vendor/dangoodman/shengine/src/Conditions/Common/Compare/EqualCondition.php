<?php
namespace WbsVendors\Dgm\Shengine\Conditions\Common\Compare;


class EqualCondition extends \WbsVendors\Dgm\Shengine\Conditions\Common\Compare\CompareCondition
{
    public function isSatisfiedBy($value)
    {
        return $this->comparator->equal($value, $this->compareWith);
    }
}