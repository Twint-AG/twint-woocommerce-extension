<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Conditions\Common\Enum;

use GzpWbsNgVendors\Dgm\ClassNameAware\ClassNameAware;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\ICondition;


class EmptyEnumCondition extends ClassNameAware implements ICondition
{
    public function isSatisfiedBy($value)
    {
        return empty($value);
    }
}