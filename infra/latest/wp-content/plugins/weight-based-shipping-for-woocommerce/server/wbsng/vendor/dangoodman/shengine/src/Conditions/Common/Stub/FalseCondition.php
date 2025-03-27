<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Conditions\Common\Stub;

use GzpWbsNgVendors\Dgm\ClassNameAware\ClassNameAware;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\ICondition;


class FalseCondition extends ClassNameAware implements ICondition
{
    public function isSatisfiedBy($value)
    {
        return false;
    }
}