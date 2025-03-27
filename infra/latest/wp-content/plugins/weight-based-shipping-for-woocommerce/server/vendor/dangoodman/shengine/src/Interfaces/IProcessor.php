<?php
namespace WbsVendors\Dgm\Shengine\Interfaces;

use Traversable;


interface IProcessor
{
    /**
     * @param Traversable|IRule[] $rules
     * @param IPackage $package
     * @return IRate[]
     */
    public function process($rules, \WbsVendors\Dgm\Shengine\Interfaces\IPackage $package);
}