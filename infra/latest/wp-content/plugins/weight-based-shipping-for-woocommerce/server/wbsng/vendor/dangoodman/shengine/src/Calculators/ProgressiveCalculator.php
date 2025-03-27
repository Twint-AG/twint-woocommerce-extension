<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Calculators;

use InvalidArgumentException;
use GzpWbsNgVendors\Dgm\NumberUnit\NumberUnit;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IAttribute;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\ICalculator;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IPackage;
use GzpWbsNgVendors\Dgm\Shengine\Model\Rate;


class ProgressiveCalculator implements ICalculator
{
    public function __construct(IAttribute $attribute, NumberUnit $attributeUnit, $cost, $step = 0, $skip = 0)
    {
        if (!self::receive($cost) || !self::receive($step) || !self::receive($skip)) {
            throw new InvalidArgumentException("Invalid progressive rate '{$cost}/{$step}/{$skip}'");
        }

        $this->attribute = $attribute;
        $this->attributeUnit = $attributeUnit;
        $this->cost = $cost;
        $this->step = $step;
        $this->skip = $skip;
    }
    
    public function calculateRatesFor(IPackage $package)
    {
        $result = 0;

        $value = $this->attribute->getValue($package);

        if ($value > $this->skip) {

            $value -= $this->skip;

            if ($this->step == 0) {
                $result = $value * $this->cost;
            } else {
                $result = $this->attributeUnit->chunks($value, $this->step) * $this->cost;
            }
        }

        return array(new Rate($result));
    }

    public function multipleRatesExpected()
    {
        return false;
    }

    private $attribute;
    private $attributeUnit;
    private $cost;
    private $step;
    private $skip;

    static private function receive(&$value)
    {
        if (!isset($value)) {
            $value = 0;
        }

        return is_numeric($value);
    }

}