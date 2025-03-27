<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Conditions\Common\Enum;


class IntersectCondition extends AbstractEnumCondition
{
    public function isSatisfiedBy($value)
    {
        return $this->intersect($value, $this->other) > 0;
    }
}