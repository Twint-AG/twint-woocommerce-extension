<?php
namespace WbsVendors\Dgm\Shengine\Model;

use Dgm\Shengine\Interfaces\ICalculator;
use Dgm\Shengine\Interfaces\IMatcher;
use Dgm\Shengine\Interfaces\IRule;
use Dgm\Shengine\Interfaces\IRuleMeta;


class Rule implements \WbsVendors\Dgm\Shengine\Interfaces\IRule
{
    public function __construct(\WbsVendors\Dgm\Shengine\Interfaces\IRuleMeta $meta, \WbsVendors\Dgm\Shengine\Interfaces\IMatcher $matcher, \WbsVendors\Dgm\Shengine\Interfaces\ICalculator $calculator)
    {
        $this->meta = $meta;
        $this->matcher = $matcher;
        $this->calculator = $calculator;
    }

    public function getMeta()
    {
        return $this->meta;
    }

    public function getMatcher()
    {
        return $this->matcher;
    }

    public function getCalculator()
    {
        return $this->calculator;
    }

    private $meta;
    private $matcher;
    private $calculator;
}