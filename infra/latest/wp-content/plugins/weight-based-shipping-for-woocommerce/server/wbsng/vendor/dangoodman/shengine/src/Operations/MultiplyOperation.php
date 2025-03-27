<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Operations;

use InvalidArgumentException;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IPackage;
use GzpWbsNgVendors\Dgm\Shengine\Processing\Registers;


class MultiplyOperation extends AbstractOperation
{
    public function __construct($multiplier)
    {
        if (!is_numeric($multiplier)) {
            throw new InvalidArgumentException();
        }

        $this->multiplier = $multiplier;
    }

    public function process(Registers $registers, IPackage $package)
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