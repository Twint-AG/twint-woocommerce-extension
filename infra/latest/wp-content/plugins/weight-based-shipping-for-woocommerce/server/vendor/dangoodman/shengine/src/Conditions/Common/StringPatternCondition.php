<?php
namespace WbsVendors\Dgm\Shengine\Conditions\Common;

use Dgm\Shengine\Interfaces\ICondition;


class StringPatternCondition implements \WbsVendors\Dgm\Shengine\Interfaces\ICondition
{
    public function __construct($pattern)
    {
        $this->pattern = '/^'.str_replace('\\*', '.*', preg_quote($pattern, '/')).'$/i';
    }

    public function isSatisfiedBy($value)
    {
        return (bool)preg_match($this->pattern, $value);
    }

    private $pattern;
}