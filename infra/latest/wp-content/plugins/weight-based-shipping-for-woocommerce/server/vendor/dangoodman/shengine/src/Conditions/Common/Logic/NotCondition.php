<?php
namespace WbsVendors\Dgm\Shengine\Conditions\Common\Logic;

use Dgm\ClassNameAware\ClassNameAware;
use Dgm\Shengine\Interfaces\ICondition;


class NotCondition extends \WbsVendors\Dgm\ClassNameAware\ClassNameAware implements \WbsVendors\Dgm\Shengine\Interfaces\ICondition
{
    public function __construct(\WbsVendors\Dgm\Shengine\Interfaces\ICondition $condition)
    {
        $this->condition = $condition;
    }

    public function isSatisfiedBy($value)
    {
        return !$this->condition->isSatisfiedBy($value);
    }

    private $condition;
}