<?php
namespace WbsVendors\Dgm\Shengine\Model;

use Dgm\Shengine\Interfaces\IRate;
use InvalidArgumentException;


class Rate implements \WbsVendors\Dgm\Shengine\Interfaces\IRate
{
    public function __construct($cost, $title = null, $taxable = null, array $meta = array())
    {
        if (!is_numeric($cost)) {
            throw new InvalidArgumentException();
        }

        $this->cost = $cost;
        $this->title = !isset($title) ? null : (string)$title;
        $this->taxable = !isset($taxable) ? null : (bool)$taxable;
        $this->meta = $meta;
    }

    public function getCost()
    {
        return $this->cost;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function isTaxable()
    {
        return $this->taxable;
    }

    public function getMeta()
    {
        return $this->meta;
    }

    private $cost;
    private $title;
    private $taxable;
    private $meta;
}