<?php
namespace WbsVendors\Dgm\Shengine\Conditions\Common;

use Dgm\Shengine\Interfaces\ICondition;


abstract class GroupCondition extends \WbsVendors\Dgm\Shengine\Conditions\Common\AbstractCondition
{
    public function __construct(array $conditions)
    {
        $this->conditions = $conditions;
    }

    /** @var ICondition[] */
    protected $conditions;
}