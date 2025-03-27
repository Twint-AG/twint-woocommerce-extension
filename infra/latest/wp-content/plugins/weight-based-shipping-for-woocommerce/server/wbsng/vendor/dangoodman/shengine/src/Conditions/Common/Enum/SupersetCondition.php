<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Conditions\Common\Enum;


class SupersetCondition extends SubsetCondition
{
    protected function isSubset($superset, $subset)
    {
        return parent::isSubset($subset, $superset);
    }
}