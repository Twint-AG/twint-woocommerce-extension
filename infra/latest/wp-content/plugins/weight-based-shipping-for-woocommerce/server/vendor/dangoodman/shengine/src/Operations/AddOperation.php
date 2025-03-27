<?php
namespace WbsVendors\Dgm\Shengine\Operations;

use Dgm\Arrays\Arrays;
use Dgm\Shengine\Interfaces\ICalculator;
use Dgm\Shengine\Interfaces\IPackage;
use Dgm\Shengine\Processing\RateRegister;
use Dgm\Shengine\Processing\Registers;
use RuntimeException;


class AddOperation extends \WbsVendors\Dgm\Shengine\Operations\AbstractOperation
{
    public function __construct(\WbsVendors\Dgm\Shengine\Interfaces\ICalculator $calculator)
    {
        $this->calculator = $calculator;
    }

    public function process(\WbsVendors\Dgm\Shengine\Processing\Registers $registers, \WbsVendors\Dgm\Shengine\Interfaces\IPackage $package)
    {
        $newRates = isset($this->calculator) ? $this->calculator->calculateRatesFor($package) : array();
        if (!$newRates) {
            return;
        }

        if (count($registers->rates) > 1 && count($newRates) > 1) {
            throw new RuntimeException("Adding up two rate sets is not supported due to ambiguity");
        }

        if (!$registers->rates) {

            $registers->rates = \WbsVendors\Dgm\Arrays\Arrays::map($newRates, function($rate) {
                return new \WbsVendors\Dgm\Shengine\Processing\RateRegister($rate);
            });

            return;
        }

        $newRegistersRates = array();
        foreach ($registers->rates as $rate1) {
            foreach ($newRates as $rate2) {
                $newRegistersRates[] = new \WbsVendors\Dgm\Shengine\Processing\RateRegister(array($rate1, $rate2));
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