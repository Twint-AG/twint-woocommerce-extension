<?php
namespace WbsVendors\Dgm\Shengine\Conditions\Common\Compare;


class NotEqualCondition extends \WbsVendors\Dgm\Shengine\Conditions\Common\Compare\CompareCondition
{
    public function isSatisfiedBy($value)
    {
        return !$this->comparator->equal($value, $this->compareWith);
    }
}