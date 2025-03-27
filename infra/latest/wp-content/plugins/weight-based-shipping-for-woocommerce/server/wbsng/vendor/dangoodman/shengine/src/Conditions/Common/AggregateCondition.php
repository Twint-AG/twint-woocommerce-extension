<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Conditions\Common;

use Dgm\Shengine\Interfaces\ICondition;


class AggregateCondition extends AbstractCondition
{
    public function isSatisfiedBy($value)
    {
        return $this->condition->isSatisfiedBy($value);
    }

    /** @var ICondition */
    protected $condition;
}