<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Conditions\Common\Compare;


class LessCondition extends CompareCondition
{
    public function isSatisfiedBy($value)
    {
        return $this->comparator->less($value, $this->compareWith);
    }
}