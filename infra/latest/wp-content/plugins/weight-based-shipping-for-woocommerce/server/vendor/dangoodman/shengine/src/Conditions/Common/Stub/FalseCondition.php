<?php
namespace WbsVendors\Dgm\Shengine\Conditions\Common\Stub;

use Dgm\ClassNameAware\ClassNameAware;
use Dgm\Shengine\Interfaces\ICondition;


class FalseCondition extends \WbsVendors\Dgm\ClassNameAware\ClassNameAware implements \WbsVendors\Dgm\Shengine\Interfaces\ICondition
{
    public function isSatisfiedBy($value)
    {
        return false;
    }
}