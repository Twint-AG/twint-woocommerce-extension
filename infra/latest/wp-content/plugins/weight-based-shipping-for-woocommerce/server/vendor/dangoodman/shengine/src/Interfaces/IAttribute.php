<?php
namespace WbsVendors\Dgm\Shengine\Interfaces;


interface IAttribute
{
    /**
     * @param IPackage $package
     * @return mixed
     */
    function getValue(\WbsVendors\Dgm\Shengine\Interfaces\IPackage $package);
}