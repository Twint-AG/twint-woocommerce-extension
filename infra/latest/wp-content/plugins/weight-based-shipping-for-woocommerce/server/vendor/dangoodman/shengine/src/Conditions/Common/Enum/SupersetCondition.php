<?php
namespace WbsVendors\Dgm\Shengine\Conditions\Common\Enum;


class SupersetCondition extends \WbsVendors\Dgm\Shengine\Conditions\Common\Enum\SubsetCondition
{
    protected function isSubset($superset, $subset)
    {
        return parent::isSubset($subset, $superset);
    }
}