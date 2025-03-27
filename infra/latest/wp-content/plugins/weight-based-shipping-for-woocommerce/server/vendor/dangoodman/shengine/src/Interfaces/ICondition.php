<?php
namespace WbsVendors\Dgm\Shengine\Interfaces;


interface ICondition
{
    /**
     * @param mixed $value
     * @return bool
     */
    public function isSatisfiedBy($value);
}