<?php /** @noinspection PhpMultipleClassesDeclarationsInOneFile */

namespace WbsVendors\Dgm\Shengine\Model;

use Dgm\Shengine\Interfaces\IItem;


class Item implements \WbsVendors\Dgm\Shengine\Interfaces\IItem
{
    public function __construct(
        $productId = null,
        $productVariationId = null,
        \WbsVendors\Dgm\Shengine\Model\Price $price = null,
        $weight = null,
        \WbsVendors\Dgm\Shengine\Model\Dimensions $dimensions = null,
        array $terms = null
    ) {
        /** @noinspection PhpDeprecationInspection
         * Public setters are deprecated. We'll make them private some day and remove the deprecated state.
         */
        {
            $this->setProductId($productId);
            $this->setProductVariationId($productVariationId);

            $defaults = ItemDefaults::get();
            $this->setPrice(isset($price) ? $price : $defaults->price);
            $this->setWeight(isset($weight) ? $weight : $defaults->weight);
            $this->setDimensions(isset($dimensions) ? $dimensions : $defaults->dimensions);

            // WoocommerceItem rejects setTerms(). Try to avoid it if possible.
            if (isset($terms)) {
                /** @noinspection PhpDeprecationInspection */
                $this->setTerms($terms);
            }
        }
    }

    public static function create()
    {
        return new static();
    }

    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * @deprecated Use the builder instead since this object expected to be immutable.
     * @param string $productId
     * @return $this
     */
    public function setProductId($productId)
    {
        $this->productId = self::receiveString($productId);
        return $this;
    }

    public function getProductVariationId()
    {
        return $this->productVariationId;
    }

    /**
     * @deprecated Use the builder instead since this object expected to be immutable.
     * @param string $productVariationId
     * @return $this
     */
    public function setProductVariationId($productVariationId)
    {
        $this->productVariationId = self::receiveString($productVariationId);
        return $this;
    }

    public function getPrice($flags = \WbsVendors\Dgm\Shengine\Model\Price::BASE)
    {
        return $this->price->getPrice($flags);
    }

    /**
     * @deprecated Use the builder instead since this object expected to be immutable.
     * @param Price $price
     * @return $this
     */
    public function setPrice(\WbsVendors\Dgm\Shengine\Model\Price $price)
    {
        $this->price = $price;
        return $this;
    }

    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @deprecated Use the builder instead since this object expected to be immutable.
     * @param float $weight
     * @return $this
     */
    public function setWeight($weight)
    {
        $this->weight = (float)$weight;
        return $this;
    }

    public function getDimensions()
    {
        return $this->dimensions;
    }

    /**
     * @deprecated Use the builder instead since this object expected to be immutable.
     * @param Dimensions $dimensions
     * @return $this
     */
    public function setDimensions(\WbsVendors\Dgm\Shengine\Model\Dimensions $dimensions)
    {
        $this->dimensions = $dimensions;
        return $this;
    }

    public function getTerms($taxonomy)
    {
        return (array)@$this->terms[$taxonomy];
    }

    /**
     * @deprecated Use the builder instead since this object expected to be immutable.
     * @param string|array $taxonomy
     * @param array|null $terms
     * @return $this
     */
    public function setTerms($taxonomy, array $terms = null)
    {
        if (is_array($taxonomy) && func_num_args() === 1) {
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $terms = $taxonomy;
        } else {
            $terms = array($taxonomy => $terms);
        }

        $this->terms = array_merge($this->terms, $terms);
        
        return $this;
    }


    private $productId;
    private $productVariationId;
    private $price;
    private $weight;
    private $dimensions;
    private $terms = array(); 

    private static function receiveString($value)
    {
        return isset($value) ? (string)$value : null;
    }
}


class ItemDefaults
{
    public $price;
    public $weight;
    public $dimensions;


    public static function get()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }


    private static $instance;

    private function __construct()
    {
        $this->price = new \WbsVendors\Dgm\Shengine\Model\Price(0, 0, 0, 0);
        $this->weight = 0;
        $this->dimensions = new \WbsVendors\Dgm\Shengine\Model\Dimensions(0, 0, 0);
    }
}
