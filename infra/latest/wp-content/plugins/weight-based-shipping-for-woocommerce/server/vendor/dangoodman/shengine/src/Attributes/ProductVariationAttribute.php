<?php
namespace WbsVendors\Dgm\Shengine\Attributes;

use Dgm\Shengine\Interfaces\IItem;


class ProductVariationAttribute extends \WbsVendors\Dgm\Shengine\Attributes\MapAttribute
{
    protected function getItemValue(\WbsVendors\Dgm\Shengine\Interfaces\IItem $item)
    {
        $id = $item->getProductVariationId();
        $id = isset($id) ? $id : $item->getProductId();
        return $id;
    }
}