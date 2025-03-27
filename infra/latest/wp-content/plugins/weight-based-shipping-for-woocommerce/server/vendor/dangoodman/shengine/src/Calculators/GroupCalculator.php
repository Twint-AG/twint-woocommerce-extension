<?php
namespace WbsVendors\Dgm\Shengine\Calculators;

use Dgm\Shengine\Interfaces\ICalculator;
use Dgm\Shengine\Interfaces\IPackage;


class GroupCalculator implements \WbsVendors\Dgm\Shengine\Interfaces\ICalculator
{
    /**
     * @param ICalculator[] $calculators
     */
    public function __construct(array $calculators)
    {
        $this->calculators = $calculators;
    }

    public function calculateRatesFor(\WbsVendors\Dgm\Shengine\Interfaces\IPackage $package)
    {
        $rates = array();
        foreach ($this->calculators as $calculator) {
            $rates = array_merge($rates, array_values($calculator->calculateRatesFor($package)));
        }

        return $rates;
    }

    public function multipleRatesExpected()
    {
        $expected = 0;
        foreach ($this->calculators as $calculator) {
            $expected += $calculator->multipleRatesExpected() ? 2 : 1;
            if ($expected > 1) {
                break;
            }
        }

        return $expected > 1;
    }

    private $calculators;
}