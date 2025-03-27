<?php
namespace WbsVendors\Dgm\Shengine\Conditions\Common\Enum;


class SubsetCondition extends \WbsVendors\Dgm\Shengine\Conditions\Common\Enum\AbstractEnumCondition
{
    public function isSatisfiedBy($value)
    {
        return $this->isSubset($value, $this->other);
    }

    protected function isSubset($subset, $superset)
    {
        $subset = $this->normalize($subset);
        return $this->intersect($subset, $superset) == count($subset);
    }
}