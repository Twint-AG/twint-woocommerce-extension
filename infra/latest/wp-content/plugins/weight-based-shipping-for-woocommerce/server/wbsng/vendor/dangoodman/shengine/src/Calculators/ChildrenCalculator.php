<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Calculators;

use GzpWbsNgVendors\Dgm\Shengine\Interfaces\ICalculator;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IPackage;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IProcessor;


class ChildrenCalculator implements ICalculator
{
    public function __construct(IProcessor $processor, $children)
    {
        $this->processor = $processor;
        $this->children = $children;
    }

    public function calculateRatesFor(IPackage $package)
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