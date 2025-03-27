<?php
namespace WbsVendors\Dgm\Shengine\Calculators;

use InvalidArgumentException;
use Dgm\NumberUnit\NumberUnit;
use Dgm\Shengine\Interfaces\IAttribute;
use Dgm\Shengine\Interfaces\ICalculator;
use Dgm\Shengine\Interfaces\IPackage;
use Dgm\Shengine\Model\Rate;


class ProgressiveCalculator implements \WbsVendors\Dgm\Shengine\Interfaces\ICalculator
{
    public function __construct(\WbsVendors\Dgm\Shengine\Interfaces\IAttribute $attribute, \WbsVendors\Dgm\NumberUnit\NumberUnit $attributeUnit, $cost, $step = 0, $skip = 0)
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
    
    public function calculateRatesFor(\WbsVendors\Dgm\Shengine\Interfaces\IPackage $package)
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

        return array(new \WbsVendors\Dgm\Shengine\Model\Rate($result));
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