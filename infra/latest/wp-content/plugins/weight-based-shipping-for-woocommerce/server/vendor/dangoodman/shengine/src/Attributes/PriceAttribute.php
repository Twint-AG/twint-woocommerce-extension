<?php
namespace WbsVendors\Dgm\Shengine\Attributes;

use Dgm\Shengine\Interfaces\IPackage;
use Dgm\Shengine\Model\Price;


class PriceAttribute extends \WbsVendors\Dgm\Shengine\Attributes\AbstractAttribute
{
    public function __construct($flags = \WbsVendors\Dgm\Shengine\Model\Price::BASE)
    {
        $this->flags = $flags;
    }

    public function getValue(\WbsVendors\Dgm\Shengine\Interfaces\IPackage $package)
    {
        return $package->getPrice($this->flags);
    }

    private $flags;
}