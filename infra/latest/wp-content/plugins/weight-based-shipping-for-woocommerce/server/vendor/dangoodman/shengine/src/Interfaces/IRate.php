<?php
namespace WbsVendors\Dgm\Shengine\Interfaces;


interface IRate
{
    /**
     * @return number
     */
    function getCost();

    /**
     * @return string|null
     */
    function getTitle();

    /**
     * @return bool|null
     */
    function isTaxable();

    /**
     * @return array
     */
    function getMeta();
}