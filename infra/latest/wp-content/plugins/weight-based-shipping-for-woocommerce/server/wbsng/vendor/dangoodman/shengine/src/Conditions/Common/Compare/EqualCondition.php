<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Conditions\Common\Compare;


class EqualCondition extends CompareCondition
{
    public function isSatisfiedBy($value)
    {
        return $this->comparator->equal($value, $this->compareWith);
    }
}