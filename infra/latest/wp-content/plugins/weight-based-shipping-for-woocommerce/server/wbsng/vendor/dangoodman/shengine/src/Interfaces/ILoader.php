<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Loader;


interface ILoader
{
    /**
     * @param object $object
     * @return mixed
     */
    public function load($object);
}