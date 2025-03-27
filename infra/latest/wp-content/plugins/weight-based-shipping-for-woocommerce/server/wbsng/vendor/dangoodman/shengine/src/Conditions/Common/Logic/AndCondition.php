<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Conditions\Common\Logic;

use GzpWbsNgVendors\Dgm\Shengine\Conditions\Common\GroupCondition;


class AndCondition extends GroupCondition
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