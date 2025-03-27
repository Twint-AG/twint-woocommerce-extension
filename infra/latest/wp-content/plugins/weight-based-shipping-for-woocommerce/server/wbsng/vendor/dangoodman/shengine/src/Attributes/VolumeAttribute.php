<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Attributes;

use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IItem;


class VolumeAttribute extends SumAttribute
{
    protected function getItemValue(IItem $item)
    {
        $dimensions = $item->getDimensions();
        $volume = $dimensions->length * $dimensions->width * $dimensions->height;
        return $volume;
    }
}