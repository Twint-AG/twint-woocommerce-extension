<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Interfaces;

use GzpWbsNgVendors\Dgm\Shengine\Model\Price;


interface IItemAggregatables
{
    const TAXONOMY_TAG = 'tag';
    const TAXONOMY_SHIPPING_CLASS = 'shipping_class';
    const TAXONOMY_CATEGORY = 'category';

    /**
     * @param int $flags
     * @return float
     */
    function getPrice($flags = Price::BASE);

    /**
     * @return float
     */
    function getWeight();

    /**
     * @param string $taxonomy Either a self::TAXONOMY_XXX constant or a custom value.
     * @return string[]
     */
    function getTerms($taxonomy);
}