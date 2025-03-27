<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Interfaces;


interface ICalculator
{
    /**
     * @param IPackage $package
     * @return IRate[]
     */
    function calculateRatesFor(IPackage $package);

    /**
     * @return bool False if no more than one rate is expected to be produced on any package
     */
    function multipleRatesExpected();
}