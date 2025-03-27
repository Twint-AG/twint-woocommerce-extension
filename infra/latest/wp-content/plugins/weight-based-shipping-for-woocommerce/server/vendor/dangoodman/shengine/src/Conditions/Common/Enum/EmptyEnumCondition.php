<?php
namespace WbsVendors\Dgm\Shengine\Conditions\Common\Enum;

use Dgm\ClassNameAware\ClassNameAware;
use Dgm\Shengine\Interfaces\ICondition;


class EmptyEnumCondition extends \WbsVendors\Dgm\ClassNameAware\ClassNameAware implements \WbsVendors\Dgm\Shengine\Interfaces\ICondition
{
    public function isSatisfiedBy($value)
    {
        return empty($value);
    }
}