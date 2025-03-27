<?php
namespace WbsVendors\Dgm\Shengine\Attributes;

use Dgm\Shengine\Interfaces\IItem;
use Dgm\Shengine\Interfaces\IPackage;


abstract class MapAttribute extends \WbsVendors\Dgm\Shengine\Attributes\AbstractAttribute
{
    public function getValue(\WbsVendors\Dgm\Shengine\Interfaces\IPackage $package)
    {
        $result = array();

        foreach ($package->getItems() as $key => $item) {
            $result[$key] = $this->getItemValue($item);
        }

        return $result;
    }

    protected abstract function getItemValue(\WbsVendors\Dgm\Shengine\Interfaces\IItem $item);
}