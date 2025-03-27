<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Calculators;

use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IAttribute;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\ICalculator;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IPackage;
use GzpWbsNgVendors\Dgm\Shengine\Model\Rate;


class AttributeMultiplierCalculator implements ICalculator
{
    public function __construct(IAttribute $attribute, $multiplier = 1)
    {
        $this->attribute = $attribute;
        $this->multiplier = $multiplier;
    }

    public function calculateRatesFor(IPackage $package)
    {
        return array(new Rate($this->attribute->getValue($package) * $this->multiplier));
    }

    public function multipleRatesExpected()
    {
        return false;
    }

    private $attribute;
    private $multiplier;
}