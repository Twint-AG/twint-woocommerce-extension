<?php
namespace WbsVendors\Dgm\Shengine\Operations;

use Dgm\ClassNameAware\ClassNameAware;
use Dgm\Shengine\Interfaces\IOperation;


abstract class AbstractOperation extends \WbsVendors\Dgm\ClassNameAware\ClassNameAware implements \WbsVendors\Dgm\Shengine\Interfaces\IOperation
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