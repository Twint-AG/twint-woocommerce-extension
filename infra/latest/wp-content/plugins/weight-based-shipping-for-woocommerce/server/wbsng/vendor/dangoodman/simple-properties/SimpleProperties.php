<?php
namespace GzpWbsNgVendors\Dgm\SimpleProperties;


class SimpleProperties
{
    public function __get($property)
    {
        return $this->{$property};
    }

    public function __isset($property)
    {
        return isset($this->{$property});
    }
}