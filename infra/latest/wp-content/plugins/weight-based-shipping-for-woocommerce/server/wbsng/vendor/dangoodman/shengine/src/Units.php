<?php
namespace GzpWbsNgVendors\Dgm\Shengine;

use GzpWbsNgVendors\Dgm\NumberUnit\NumberUnit;
use GzpWbsNgVendors\Dgm\SimpleProperties\SimpleProperties;


/**
 * @property-read NumberUnit $weight
 * @property-read NumberUnit $dimension
 * @property-read NumberUnit $price
 * @property-read NumberUnit $volume
 */
class Units extends SimpleProperties
{
    public function __construct(NumberUnit $price, NumberUnit $weight, NumberUnit $dimension, NumberUnit $volume)
    {
        $this->weight = $weight;
        $this->dimension = $dimension;
        $this->price = $price;
        $this->volume = $volume;
    }

    static public function fromPrecisions($price, $weight, $dimension, $volume = null)
    {
        return new self(
            new NumberUnit($price),
            new NumberUnit($weight),
            new NumberUnit($dimension),
            new NumberUnit(isset($volume) ? $volume : pow($dimension, 3))
        );
    }

    protected $weight;
    protected $dimension;
    protected $price;
    protected $volume;
}