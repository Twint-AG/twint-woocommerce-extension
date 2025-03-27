<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Conditions\Common\Compare;


class LessOrEqualCondition extends CompareCondition
{
    public function isSatisfiedBy($value)
    {
        return $this->comparator->less($value, $this->compareWith, true);
    }
}