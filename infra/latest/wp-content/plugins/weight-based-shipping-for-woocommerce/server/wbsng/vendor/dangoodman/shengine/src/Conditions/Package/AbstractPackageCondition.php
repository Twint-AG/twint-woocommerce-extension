<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Conditions\Package;

use GzpWbsNgVendors\Dgm\Shengine\Conditions\Common\AbstractCondition;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IPackage;


abstract class AbstractPackageCondition extends AbstractCondition
{
    public function isSatisfiedBy($package)
    {
        return $this->isSatisfiedByPackage($package);
    }

    abstract protected function isSatisfiedByPackage(IPackage $package);
}