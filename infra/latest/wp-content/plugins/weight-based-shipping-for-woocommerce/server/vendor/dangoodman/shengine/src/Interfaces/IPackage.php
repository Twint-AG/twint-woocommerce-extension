<?php
namespace WbsVendors\Dgm\Shengine\Interfaces;

use Dgm\Shengine\Model\Customer;
use Dgm\Shengine\Model\Destination;


interface IPackage extends \WbsVendors\Dgm\Shengine\Interfaces\IItemAggregatables
{
    const NONE_VIRTUAL_TERM_ID = '-1';

    /**
     * @return bool
     */
    function hasCustomPrice();

    /**
     * @return IItem[]
     */
    function getItems();
    
    /**
     * @return bool
     */
    function isEmpty();

    /**
     * @return Destination|null
     */
    function getDestination();

    /**
     * @return Customer|null
     */
    function getCustomer();

    /**
     * @return string[]
     */
    function getCoupons();

    /**
     * @param IGrouping $splitBy
     * @param ICondition|callable $filterBy
     * @param bool $requireAllPackages
     * @return IPackage|null
     */
    function splitFilterMerge(\WbsVendors\Dgm\Shengine\Interfaces\IGrouping $splitBy, $filterBy, $requireAllPackages);

    /**
     * Implementations might return the original $package, even multiple times and/or create new packages even if they
     * are exactly equal to the original one. No assumptions on that should be made since IPackage is a Value Object.
     *
     * @param IGrouping $by
     * @return IPackage[]
     */
    function split(\WbsVendors\Dgm\Shengine\Interfaces\IGrouping $by);

    /**
     * @param IPackage[]|IPackage $other
     * @return IPackage
     */
    function exclude($other);
}
