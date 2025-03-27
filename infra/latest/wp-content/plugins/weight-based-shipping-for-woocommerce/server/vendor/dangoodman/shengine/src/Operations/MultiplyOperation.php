<?php
namespace WbsVendors\Dgm\Shengine\Operations;

use InvalidArgumentException;
use Dgm\Shengine\Interfaces\IPackage;
use Dgm\Shengine\Processing\Registers;


class MultiplyOperation extends \WbsVendors\Dgm\Shengine\Operations\AbstractOperation
{
    public function __construct($multiplier)
    {
        if (!is_numeric($multiplier)) {
            throw new InvalidArgumentException();
        }

        $this->multiplier = $multiplier;
    }

    public function process(\WbsVendors\Dgm\Shengine\Processing\Registers $registers, \WbsVendors\Dgm\Shengine\Interfaces\IPackage $package)
    {
        foreach ($registers->rates as $rate) {
            $rate->cost *= $this->multiplier;
        }
    }

    public function getType()
    {
        return self::MODIFIER;
    }

    private $multiplier;
}