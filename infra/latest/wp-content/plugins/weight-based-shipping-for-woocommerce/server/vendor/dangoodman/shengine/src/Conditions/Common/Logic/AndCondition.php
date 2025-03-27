<?php
namespace WbsVendors\Dgm\Shengine\Conditions\Common\Logic;

use Dgm\Shengine\Conditions\Common\GroupCondition;


class AndCondition extends \WbsVendors\Dgm\Shengine\Conditions\Common\GroupCondition
{
    public function isSatisfiedBy($value)
    {
        foreach ($this->conditions as $condition) {
            if (!$condition->isSatisfiedBy($value)) {
                return false;
            }
        }

        return true;
    }
}