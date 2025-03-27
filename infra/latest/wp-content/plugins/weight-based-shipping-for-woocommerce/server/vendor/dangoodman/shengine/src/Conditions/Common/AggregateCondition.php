<?php
namespace WbsVendors\Dgm\Shengine\Conditions\Common;

use Dgm\Shengine\Interfaces\ICondition;


class AggregateCondition extends \WbsVendors\Dgm\Shengine\Conditions\Common\AbstractCondition
{
    public function isSatisfiedBy($value)
    {
        return $this->condition->isSatisfiedBy($value);
    }

    /** @var ICondition */
    protected $condition;
}