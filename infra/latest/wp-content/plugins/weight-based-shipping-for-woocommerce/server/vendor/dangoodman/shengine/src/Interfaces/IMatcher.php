<?php
namespace WbsVendors\Dgm\Shengine\Interfaces;


interface IMatcher
{
    /**
     * @param IPackage $package
     * @return IPackage|null
     */
    function getMatchingPackage(\WbsVendors\Dgm\Shengine\Interfaces\IPackage $package);

    /**
     * @return bool
     */
    function isCapturingMatcher();
}