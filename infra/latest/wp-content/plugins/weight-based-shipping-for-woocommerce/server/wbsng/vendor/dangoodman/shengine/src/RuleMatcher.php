<?php
namespace GzpWbsNgVendors\Dgm\Shengine;

use GzpWbsNgVendors\Dgm\Shengine\Interfaces\ICondition;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IMatcher;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IPackage;


class RuleMatcher implements IMatcher
{
    public function __construct(RuleMatcherMeta $meta, ICondition $condition)
    {
        $this->meta = $meta;
        $this->condition = $condition;
    }

    public function getMatchingPackage(IPackage $package)
    {
        return $package->splitFilterMerge($this->meta->grouping, $this->condition, $this->meta->requireAllPackages);
    }

    public function isCapturingMatcher()
    {
        return $this->meta->capture;
    }

    private $meta;
    private $condition;
}
