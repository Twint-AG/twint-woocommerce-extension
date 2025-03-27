<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Conditions\Common\Compare;


class GreaterCondition extends CompareCondition
{
    public function isSatisfiedBy($value)
    {
        return $this->comparator->greater($value, $this->compareWith);
    }
}