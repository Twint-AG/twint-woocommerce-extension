<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Attributes;

use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IItem;


class ProductVariationAttribute extends MapAttribute
{
    protected function getItemValue(IItem $item)
    {
        $id = $item->getProductVariationId();
        $id = isset($id) ? $id : $item->getProductId();
        return $id;
    }
}