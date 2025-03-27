<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Conditions\Common;

use GzpWbsNgVendors\Dgm\Shengine\Interfaces\ICondition;


class StringPatternCondition implements ICondition
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