<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Conditions\Common\Logic;

use GzpWbsNgVendors\Dgm\ClassNameAware\ClassNameAware;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\ICondition;


class NotCondition extends ClassNameAware implements ICondition
{
    public function __construct(ICondition $condition)
    {
        $this->condition = $condition;
    }

    public function isSatisfiedBy($value)
    {
        return !$this->condition->isSatisfiedBy($value);
    }

    private $condition;
}