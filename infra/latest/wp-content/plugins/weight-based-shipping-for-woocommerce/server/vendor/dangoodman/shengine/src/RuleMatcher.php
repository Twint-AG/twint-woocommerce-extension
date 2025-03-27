<?php
namespace WbsVendors\Dgm\Shengine;

use Dgm\Shengine\Interfaces\ICondition;
use Dgm\Shengine\Interfaces\IMatcher;
use Dgm\Shengine\Interfaces\IPackage;


class RuleMatcher implements \WbsVendors\Dgm\Shengine\Interfaces\IMatcher
{
    public function __construct(\WbsVendors\Dgm\Shengine\RuleMatcherMeta $meta, \WbsVendors\Dgm\Shengine\Interfaces\ICondition $condition)
    {
        $this->meta = $meta;
        $this->condition = $condition;
    }

    public function getMatchingPackage(\WbsVendors\Dgm\Shengine\Interfaces\IPackage $package)
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
