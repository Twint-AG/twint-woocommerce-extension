<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Operations;

use Exception;
use Dgm\Shengine\Interfaces\IOperation;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IPackage;
use GzpWbsNgVendors\Dgm\Shengine\Processing\Registers;


class GroupOperation extends AbstractOperation
{
    public function __construct($operations)
    {
        /** @var IOperation[] $operations */

        $multipleRatesExpected = false;
        foreach ($operations as $idx => $operation) {
            if ($multipleRatesExpected && !$operation->canOperateOnMultipleRates()) {
                $operationClass = new \ReflectionClass($operation);
                throw new Exception("
                    Operation #{$idx} ({$operationClass->getShortName()}) cannot operate on multiple rates
                    which are expected to be returned from previous operations
                ");
            }

            $type = $operation->getType();
            if ($type == self::OTHER) {
                $multipleRatesExpected = true;
            } else if ($type == self::AGGREGATOR) {
                $multipleRatesExpected = false;
            }
        }

        $this->operations = $operations;
    }

    public function process(Registers $registers, IPackage $package)
    {
        foreach ($this->operations as $operation) {
            $operation->process($registers, $package);
        }
    }

    public function getType()
    {
        $combinedType = self::MODIFIER;
        foreach ($this->operations as $operation) {
            if (($type = $operation->getType()) != self::MODIFIER) {
                $combinedType = $type;
            }
        }

        return $combinedType;
    }

    private $operations;
}