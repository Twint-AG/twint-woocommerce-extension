<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Conditions\Common;

use Dgm\Shengine\Interfaces\ICondition;


abstract class GroupCondition extends AbstractCondition
{
    public function __construct(array $conditions)
    {
        $this->conditions = $conditions;
    }

    /** @var ICondition[] */
    protected $conditions;
}