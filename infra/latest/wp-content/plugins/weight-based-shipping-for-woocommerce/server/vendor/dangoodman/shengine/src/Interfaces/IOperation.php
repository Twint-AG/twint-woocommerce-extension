<?php
namespace WbsVendors\Dgm\Shengine\Interfaces;

use Dgm\Shengine\Processing\Registers;


interface IOperation
{
    // Operation types
    const OTHER = null;
    const AGGREGATOR = 1;  // operation will return one or no rates no matter how many rates passed in
    const MODIFIER = 2;    // operation will return same number of rates passed in any case

    /**
     * @param Registers $registers
     * @param IPackage $package
     */
    function process(\WbsVendors\Dgm\Shengine\Processing\Registers $registers, \WbsVendors\Dgm\Shengine\Interfaces\IPackage $package);

    /**
     * @return int One of the operation type constants
     */
    function getType();

    /**
     * @return bool
     */
    function canOperateOnMultipleRates();
}