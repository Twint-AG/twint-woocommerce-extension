<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Attributes;

use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IItem;


class ItemAttribute extends MapAttribute
{
    protected function getItemValue(IItem $item)
    {
        return spl_object_hash($item);
    }
}