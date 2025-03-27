<?php
namespace WbsVendors\Dgm\Shengine\Conditions\Package;

use Dgm\Shengine\Conditions\Common\AbstractCondition;
use Dgm\Shengine\Interfaces\IPackage;


abstract class AbstractPackageCondition extends \WbsVendors\Dgm\Shengine\Conditions\Common\AbstractCondition
{
    public function isSatisfiedBy($package)
    {
        return $this->isSatisfiedByPackage($package);
    }

    abstract protected function isSatisfiedByPackage(\WbsVendors\Dgm\Shengine\Interfaces\IPackage $package);
}