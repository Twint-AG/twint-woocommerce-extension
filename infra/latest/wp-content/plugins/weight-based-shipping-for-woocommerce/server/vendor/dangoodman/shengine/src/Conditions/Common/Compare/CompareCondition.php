<?php
namespace WbsVendors\Dgm\Shengine\Conditions\Common\Compare;

use Dgm\Comparator\IComparator;
use Dgm\Shengine\Conditions\Common\AbstractCondition;


abstract class CompareCondition extends \WbsVendors\Dgm\Shengine\Conditions\Common\AbstractCondition
{
    public function __construct($compareWith, \WbsVendors\Dgm\Comparator\IComparator $comparator)
    {
        $this->compareWith = $compareWith;
        $this->comparator = $comparator;
    }


    protected $compareWith;
    protected $comparator;
}