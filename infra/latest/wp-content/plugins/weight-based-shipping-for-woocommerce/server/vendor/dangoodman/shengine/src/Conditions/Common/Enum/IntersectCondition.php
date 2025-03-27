<?php
namespace WbsVendors\Dgm\Shengine\Conditions\Common\Enum;


class IntersectCondition extends \WbsVendors\Dgm\Shengine\Conditions\Common\Enum\AbstractEnumCondition
{
    public function isSatisfiedBy($value)
    {
        return $this->intersect($value, $this->other) > 0;
    }
}