<?php
namespace WbsVendors\Dgm\Shengine\Attributes;

use Dgm\Shengine\Interfaces\IItem;


class ItemDimensionsAttribute extends \WbsVendors\Dgm\Shengine\Attributes\MapAttribute
{
    protected function getItemValue(\WbsVendors\Dgm\Shengine\Interfaces\IItem $item)
    {
        $dimensions = $item->getDimensions();
        $box = array($dimensions->length, $dimensions->width, $dimensions->height);
        return $box;
    }
}