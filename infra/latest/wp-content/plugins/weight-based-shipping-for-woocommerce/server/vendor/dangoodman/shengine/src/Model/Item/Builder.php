<?php
namespace WbsVendors\Dgm\Shengine\Model\Item;

use Dgm\Shengine\Model\Dimensions;
use Dgm\Shengine\Model\Item;
use Dgm\Shengine\Model\Price;



class Builder
{
    /**
     * @return static
     */
    public static function create()
    {
        return new static();
    }

    /**
     * @return Item
     */
    public function build()
    {
        return new \WbsVendors\Dgm\Shengine\Model\Item(
            $this->productId,
            $this->productVariationId,
            $this->price,
            $this->weight,
            $this->dimensions,
            $this->terms
        );
    }

    public function setProductId($productId)
    {
        $this->productId = $productId;
        return $this;
    }

    public function setProductVariationId($productVariationId)
    {
        $this->productVariationId = $productVariationId;
        return $this;
    }

    public function setPrice(\WbsVendors\Dgm\Shengine\Model\Price $price)
    {
        $this->price = $price;
        return $this;
    }

    public function setWeight($weight)
    {
        $this->weight = $weight;
        return $this;
    }

    public function setDimensions(\WbsVendors\Dgm\Shengine\Model\Dimensions $dimensions)
    {
        $this->dimensions = $dimensions;
        return $this;
    }

    public function setTerms(array $terms)
    {
        $this->terms = $terms;
        return $this;
    }

    private $productId;
    private $productVariationId;
    private $price;
    private $weight;
    private $dimensions;
    private $terms = array();
}
