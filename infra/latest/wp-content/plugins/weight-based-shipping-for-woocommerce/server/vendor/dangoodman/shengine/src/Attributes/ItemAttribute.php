<?php
namespace WbsVendors\Dgm\Shengine\Attributes;

use Dgm\Shengine\Interfaces\IItem;


class ItemAttribute extends \WbsVendors\Dgm\Shengine\Attributes\MapAttribute
{
    protected function getItemValue(\WbsVendors\Dgm\Shengine\Interfaces\IItem $item)
    {
        return spl_object_hash($item);
    }
}