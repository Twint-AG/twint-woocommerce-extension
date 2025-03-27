<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Model;

use GzpWbsNgVendors\Dgm\SimpleProperties\SimpleProperties;


/**
 * @property-read float $length
 * @property-read float $width
 * @property-read float $height
 */
class Dimensions extends SimpleProperties
{
    public function __construct($length, $width, $height)
    {
        $this->length = (float)$length;
        $this->width = (float)$width;
        $this->height = (float)$height;
    }

    protected $length;
    protected $width;
    protected $height;
}