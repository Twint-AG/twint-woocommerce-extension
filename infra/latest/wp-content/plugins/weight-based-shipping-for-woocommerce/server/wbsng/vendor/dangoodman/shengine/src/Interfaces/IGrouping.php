<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Interfaces;


interface IGrouping
{
    /**
     * @param IItem $item
     * @return string[]
     */
    function getPackageIds(IItem $item);

    /**
     * @return bool False if no more than one package is expected to be produced by this grouping. Expected to be true
     *              for all groupings except {@see NoopGrouping}.
     */
    function multiplePackagesExpected();
}
