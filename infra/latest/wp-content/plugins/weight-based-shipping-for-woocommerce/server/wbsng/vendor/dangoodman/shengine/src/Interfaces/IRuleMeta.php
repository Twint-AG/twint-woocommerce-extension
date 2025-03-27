<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Interfaces;


interface IRuleMeta
{
    /**
     * @return string|null
     */
    public function getTitle();

    /**
     * @return bool|null
     */
    public function isTaxable();
}