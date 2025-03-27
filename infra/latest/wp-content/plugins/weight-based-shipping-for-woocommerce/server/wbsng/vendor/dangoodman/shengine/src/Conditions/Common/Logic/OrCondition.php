<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Conditions\Common\Logic;

use GzpWbsNgVendors\Dgm\Shengine\Conditions\Common\GroupCondition;


class OrCondition extends GroupCondition
{
    public function isSatisfiedBy($value)
    {
        foreach ($this->conditions as $condition) {
            if ($condition->isSatisfiedBy($value)) {
                return true;
            }
        }

        return false;
    }
}