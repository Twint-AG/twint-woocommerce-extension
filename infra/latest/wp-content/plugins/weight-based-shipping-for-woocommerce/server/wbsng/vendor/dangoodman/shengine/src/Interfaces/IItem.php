<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Interfaces;

use Dgm\Shengine\Model\Dimensions;


interface IItem extends IItemAggregatables
{
    /**
     * @return string
     */
    function getProductId();

    /**
     * @return string
     */
    function getProductVariationId();

    /**
     * @return Dimensions
     */
    function getDimensions();
}