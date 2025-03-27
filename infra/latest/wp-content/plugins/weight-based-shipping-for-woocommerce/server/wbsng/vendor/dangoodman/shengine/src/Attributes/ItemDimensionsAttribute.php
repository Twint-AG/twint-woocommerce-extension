<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Attributes;

use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IItem;


class ItemDimensionsAttribute extends MapAttribute
{
    protected function getItemValue(IItem $item)
    {
        $dimensions = $item->getDimensions();
        $box = array($dimensions->length, $dimensions->width, $dimensions->height);
        return $box;
    }
}