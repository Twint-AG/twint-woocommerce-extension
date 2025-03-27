<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Calculators;

use GzpWbsNgVendors\Dgm\Shengine\Interfaces\ICalculator;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IPackage;


class GroupCalculator implements ICalculator
{
    /**
     * @param ICalculator[] $calculators
     */
    public function __construct(array $calculators)
    {
        $this->calculators = $calculators;
    }

    public function calculateRatesFor(IPackage $package)
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