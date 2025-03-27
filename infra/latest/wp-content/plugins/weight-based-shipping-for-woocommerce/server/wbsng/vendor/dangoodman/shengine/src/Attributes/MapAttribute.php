<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Attributes;

use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IItem;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IPackage;


abstract class MapAttribute extends AbstractAttribute
{
    public function getValue(IPackage $package)
    {
        $result = array();

        foreach ($package->getItems() as $key => $item) {
            $result[$key] = $this->getItemValue($item);
        }

        return $result;
    }

    protected abstract function getItemValue(IItem $item);
}