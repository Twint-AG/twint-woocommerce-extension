<?php
namespace WbsVendors\Dgm\Shengine\Attributes;

use Dgm\Shengine\Interfaces\IItem;


class ProductAttribute extends \WbsVendors\Dgm\Shengine\Attributes\MapAttribute
{
    protected function getItemValue(\WbsVendors\Dgm\Shengine\Interfaces\IItem $item)
    {
        return $item->getProductId();
    }
}