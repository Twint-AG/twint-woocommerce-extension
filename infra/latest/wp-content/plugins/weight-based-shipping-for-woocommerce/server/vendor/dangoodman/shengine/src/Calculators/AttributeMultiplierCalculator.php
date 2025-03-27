<?php
namespace WbsVendors\Dgm\Shengine\Calculators;

use Dgm\Shengine\Interfaces\IAttribute;
use Dgm\Shengine\Interfaces\ICalculator;
use Dgm\Shengine\Interfaces\IPackage;
use Dgm\Shengine\Model\Rate;


class AttributeMultiplierCalculator implements \WbsVendors\Dgm\Shengine\Interfaces\ICalculator
{
    public function __construct(\WbsVendors\Dgm\Shengine\Interfaces\IAttribute $attribute, $multiplier = 1)
    {
        $this->attribute = $attribute;
        $this->multiplier = $multiplier;
    }

    public function calculateRatesFor(\WbsVendors\Dgm\Shengine\Interfaces\IPackage $package)
    {
        return array(new \WbsVendors\Dgm\Shengine\Model\Rate($this->attribute->getValue($package) * $this->multiplier));
    }

    public function multipleRatesExpected()
    {
        return false;
    }

    private $attribute;
    private $multiplier;
}