<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Operations;

use GzpWbsNgVendors\Dgm\Arrays\Arrays;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\ICalculator;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IPackage;
use GzpWbsNgVendors\Dgm\Shengine\Processing\RateRegister;
use GzpWbsNgVendors\Dgm\Shengine\Processing\Registers;
use RuntimeException;


class AddOperation extends AbstractOperation
{
    public function __construct(ICalculator $calculator)
    {
        $this->calculator = $calculator;
    }

    public function process(Registers $registers, IPackage $package)
    {
        $newRates = isset($this->calculator) ? $this->calculator->calculateRatesFor($package) : array();
        if (!$newRates) {
            return;
        }

        if (count($registers->rates) > 1 && count($newRates) > 1) {
            throw new RuntimeException("Adding up two rate sets is not supported due to ambiguity");
        }

        if (!$registers->rates) {

            $registers->rates = Arrays::map($newRates, function($rate) {
                return new RateRegister($rate);
            });

            return;
        }

        $newRegistersRates = array();
        foreach ($registers->rates as $rate1) {
            foreach ($newRates as $rate2) {
                $newRegistersRates[] = new RateRegister(array($rate1, $rate2));
            }
        }

        $registers->rates = $newRegistersRates;
    }

    public function getType()
    {
        return $this->calculator->multipleRatesExpected() ? self::OTHER : self::MODIFIER;
    }

    public function canOperateOnMultipleRates()
    {
        return !$this->calculator->multipleRatesExpected();
    }

    private $calculator;
}