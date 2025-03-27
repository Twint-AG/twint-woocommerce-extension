<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Conditions\Package;

use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IAttribute;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\ICondition;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IPackage;


class PackageAttributeCondition extends AbstractPackageCondition
{
    public function __construct(ICondition $condition, IAttribute $attribute)
    {
        $this->condition = $condition;
        $this->attribute = $attribute;
    }

    public function isSatisfiedByPackage(IPackage $package)
    {
        return $this->condition->isSatisfiedBy($this->attribute->getValue($package));
    }

    private $condition;
    private $attribute;
}