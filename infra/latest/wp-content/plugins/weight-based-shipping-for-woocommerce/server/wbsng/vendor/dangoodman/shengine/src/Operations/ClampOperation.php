<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Operations;

use GzpWbsNgVendors\Dgm\Range\Range;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IPackage;
use GzpWbsNgVendors\Dgm\Shengine\Processing\Registers;


class ClampOperation extends AbstractOperation
{
    public function __construct(Range $range)
    {
        $this->range = $range;
    }

    public function process(Registers $registers, IPackage $package)
    {
        foreach ($registers->rates as $rate) {
            $rate->cost = $this->range->clamp($rate->cost);
        }
    }

    public function getType()
    {
        return self::MODIFIER;
    }

    private $range;
}