<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Model;

use GzpWbsNgVendors\Dgm\Shengine\Interfaces\ICalculator;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IMatcher;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IRule;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IRuleMeta;


class Rule implements IRule
{
    public function __construct(IRuleMeta $meta, IMatcher $matcher, ICalculator $calculator)
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