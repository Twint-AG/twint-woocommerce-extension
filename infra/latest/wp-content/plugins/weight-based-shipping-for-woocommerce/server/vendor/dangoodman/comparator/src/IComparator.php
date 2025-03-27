<?php
namespace WbsVendors\Dgm\Comparator;


interface IComparator
{
    /**
     * @param mixed $a
     * @param mixed $b
     * @return number -1|0|1
     */
    function compare($a, $b);


    # Helper methods.
    # Must be implemented as C#-like extension methods, i.e. calling compare() to compute their results.

    function equal($a, $b);
    function less($a, $b, $orEqual = false);
    function greater($a, $b, $orEqual = false);
}