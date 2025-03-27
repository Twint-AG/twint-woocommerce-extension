<?php
namespace WbsVendors\Dgm\Shengine\Attributes;

use Dgm\Shengine\Interfaces\IItem;


class VolumeAttribute extends \WbsVendors\Dgm\Shengine\Attributes\SumAttribute
{
    protected function getItemValue(\WbsVendors\Dgm\Shengine\Interfaces\IItem $item)
    {
        $dimensions = $item->getDimensions();
        $volume = $dimensions->length * $dimensions->width * $dimensions->height;
        return $volume;
    }
}