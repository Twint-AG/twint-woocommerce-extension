<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Operations;

use GzpWbsNgVendors\Dgm\ClassNameAware\ClassNameAware;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IOperation;


abstract class AbstractOperation extends ClassNameAware implements IOperation
{
    public function getType()
    {
        return self::OTHER;
    }

    public function canOperateOnMultipleRates()
    {
        return true;
    }
}