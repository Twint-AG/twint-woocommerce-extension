<?php
namespace WbsVendors\Dgm\Shengine\Model;


class Address
{
    public function __construct($line1, $line2 = null)
    {
        $this->line1 = $line1;
        $this->line2 = $line2;
    }

    public function getLine1()
    {
        return $this->line1;
    }

    public function getLine2()
    {
        return $this->line2;
    }

    private $line1;
    private $line2;
}