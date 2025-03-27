<?php
namespace WbsVendors\Dgm\Shengine\Interfaces;


interface IRule
{
    /**
     * @return IRuleMeta
     */
    public function getMeta();

    /**
     * @return IMatcher
     */
    public function getMatcher();

    /**
     * @return ICalculator
     */
    public function getCalculator();
}