<?php
namespace WbsVendors\Dgm\Shengine;

use Dgm\NumberUnit\NumberUnit;
use Dgm\SimpleProperties\SimpleProperties;


/**
 * @property-read NumberUnit $weight
 * @property-read NumberUnit $dimension
 * @property-read NumberUnit $price
 * @property-read NumberUnit $volume
 */
class Units extends \WbsVendors\Dgm\SimpleProperties\SimpleProperties
{
    public function __construct(\WbsVendors\Dgm\NumberUnit\NumberUnit $price, \WbsVendors\Dgm\NumberUnit\NumberUnit $weight, \WbsVendors\Dgm\NumberUnit\NumberUnit $dimension, \WbsVendors\Dgm\NumberUnit\NumberUnit $volume)
    {
        $this->weight = $weight;
        $this->dimension = $dimension;
        $this->price = $price;
        $this->volume = $volume;
    }

    static public function fromPrecisions($price, $weight, $dimension, $volume = null)
    {
        return new self(
            new \WbsVendors\Dgm\NumberUnit\NumberUnit($price),
            new \WbsVendors\Dgm\NumberUnit\NumberUnit($weight),
            new \WbsVendors\Dgm\NumberUnit\NumberUnit($dimension),
            new \WbsVendors\Dgm\NumberUnit\NumberUnit(isset($volume) ? $volume : pow($dimension, 3))
        );
    }

    protected $weight;
    protected $dimension;
    protected $price;
    protected $volume;
}