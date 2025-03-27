<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Conditions\Common\Compare;

use GzpWbsNgVendors\Dgm\Comparator\IComparator;
use GzpWbsNgVendors\Dgm\Range\Range;
use GzpWbsNgVendors\Dgm\Shengine\Conditions\Common\AbstractCondition;


class BetweenCondition extends AbstractCondition
{
    public function __construct(Range $range, IComparator $comparator)
    {
        $this->range = $range;
        $this->comparator = $comparator;
    }

    public function isSatisfiedBy($value)
    {
        return $this->range->includes($value, $this->comparator);
    }

    private $range;
    private $comparator;
}