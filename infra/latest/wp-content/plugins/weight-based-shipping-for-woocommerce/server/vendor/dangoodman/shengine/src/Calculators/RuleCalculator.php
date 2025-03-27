<?php
namespace WbsVendors\Dgm\Shengine\Calculators;

use Dgm\Shengine\Interfaces\ICalculator;
use Dgm\Shengine\Interfaces\IGrouping;
use Dgm\Shengine\Interfaces\IOperation;
use Dgm\Shengine\Interfaces\IPackage;
use Dgm\Shengine\Processing\RateRegister;
use Dgm\Shengine\Processing\Registers;
use RuntimeException;


class RuleCalculator implements \WbsVendors\Dgm\Shengine\Interfaces\ICalculator
{
    public function __construct(\WbsVendors\Dgm\Shengine\Interfaces\IOperation $operation, \WbsVendors\Dgm\Shengine\Interfaces\IGrouping $grouping)
    {
        if ($this->operationMayProduceMultipleRates($operation) && $grouping->multiplePackagesExpected()) {
            self::throwAmbiguityError();
        }

        $this->operation = $operation;
        $this->grouping = $grouping;
    }

    public function calculateRatesFor(\WbsVendors\Dgm\Shengine\Interfaces\IPackage $package)
    {
        $subPackageRateSets = array();

        foreach ($package->split($this->grouping) as $subPackage) {
            $registers = new \WbsVendors\Dgm\Shengine\Processing\Registers();
            $this->operation->process($registers, $subPackage);
            $subPackageRateSets[] = $registers->rates;
        }

        if (count($subPackageRateSets) > 1) {

            $rate = null;

            foreach ($subPackageRateSets as $rates) {

                if (count($rates) != 1) {
                    if ($rates) {
                        self::throwAmbiguityError();
                    } else {
                        continue;
                    }
                }

                if (!isset($rate)) {
                    $rate = new \WbsVendors\Dgm\Shengine\Processing\RateRegister();
                }

                $rate->add(reset($rates));
            }

            $subPackageRateSets = array(isset($rate) ? array($rate) : array());
        }

        if (!($rates = reset($subPackageRateSets))) {
            $rates = array();
        }

        return $rates;
    }

    public function multipleRatesExpected()
    {
        return
            !$this->grouping->multiplePackagesExpected() &&
            $this->operationMayProduceMultipleRates($this->operation);
    }

    private $operation;
    private $grouping;

    private static function throwAmbiguityError()
    {
        throw new RuntimeException('Cannot aggregate multiple rates for multiple packages');
    }

    private static function operationMayProduceMultipleRates(\WbsVendors\Dgm\Shengine\Interfaces\IOperation $operation)
    {
        return !in_array(
            $operation->getType(),
            array(\WbsVendors\Dgm\Shengine\Interfaces\IOperation::MODIFIER, \WbsVendors\Dgm\Shengine\Interfaces\IOperation::AGGREGATOR)
        );
    }
}