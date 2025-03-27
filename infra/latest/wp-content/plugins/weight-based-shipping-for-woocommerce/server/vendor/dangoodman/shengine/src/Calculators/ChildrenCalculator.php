<?php
namespace WbsVendors\Dgm\Shengine\Calculators;

use Dgm\Shengine\Interfaces\ICalculator;
use Dgm\Shengine\Interfaces\IPackage;
use Dgm\Shengine\Interfaces\IProcessor;


class ChildrenCalculator implements \WbsVendors\Dgm\Shengine\Interfaces\ICalculator
{
    public function __construct(\WbsVendors\Dgm\Shengine\Interfaces\IProcessor $processor, $children)
    {
        $this->processor = $processor;
        $this->children = $children;
    }

    public function calculateRatesFor(\WbsVendors\Dgm\Shengine\Interfaces\IPackage $package)
    {
        return $this->processor->process($this->children, $package);
    }

    public function multipleRatesExpected()
    {
        return !empty($this->children);
    }

    private $processor;
    private $children;
}