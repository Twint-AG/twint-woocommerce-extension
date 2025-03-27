<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Attributes;

use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IItem;


class ProductAttribute extends MapAttribute
{
    protected function getItemValue(IItem $item)
    {
        return $item->getProductId();
    }
}