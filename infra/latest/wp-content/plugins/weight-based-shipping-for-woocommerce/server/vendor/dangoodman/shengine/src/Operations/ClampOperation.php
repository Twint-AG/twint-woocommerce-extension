<?php
namespace WbsVendors\Dgm\Shengine\Operations;

use Dgm\Range\Range;
use Dgm\Shengine\Interfaces\IPackage;
use Dgm\Shengine\Processing\Registers;


class ClampOperation extends \WbsVendors\Dgm\Shengine\Operations\AbstractOperation
{
    public function __construct(\WbsVendors\Dgm\Range\Range $range)
    {
        $this->range = $range;
    }

    public function process(\WbsVendors\Dgm\Shengine\Processing\Registers $registers, \WbsVendors\Dgm\Shengine\Interfaces\IPackage $package)
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