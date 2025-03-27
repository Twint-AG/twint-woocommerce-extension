<?php
namespace WbsVendors\Dgm\Shengine\Grouping;

use Dgm\Shengine\Interfaces\IAttribute;
use Dgm\Shengine\Interfaces\IGrouping;
use Dgm\Shengine\Interfaces\IItem;
use Dgm\Shengine\Interfaces\IPackage;
use Dgm\Shengine\Model\Package;


class AttributeGrouping implements \WbsVendors\Dgm\Shengine\Interfaces\IGrouping
{
    public function __construct(\WbsVendors\Dgm\Shengine\Interfaces\IAttribute $attribute)
    {
        $this->attribute = $attribute;
    }

    public function getPackageIds(\WbsVendors\Dgm\Shengine\Interfaces\IItem $item)
    {
        return self::ids($this->attribute->getValue(new \WbsVendors\Dgm\Shengine\Model\Package(array($item))));
    }

    public function multiplePackagesExpected()
    {
        return true;
    }

    private $attribute;

    private static function ids($value, $allowArray = true)
    {
        $ids = array();

        if (is_array($value) && $allowArray) {
            foreach ($value as $item) {
                $ids = array_merge($ids, self::ids($item, false));
            }
        } else if (is_object($value)) {
            /** @noinspection PhpParamsInspection */
            $ids[] = spl_object_hash($value);
        } else {
            $ids[] = (string)$value;
        }

        $ids = array_unique($ids);

        return $ids;
    }
}
