<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Model;


class Price
{
    const BASE = 0;
    const WITH_DISCOUNT = 1;
    const WITH_TAX = 2;


    public function __construct($basePrice, $tax, $priceDiscount, $taxDiscount)
    {
        $this->basePrice = $basePrice;
        $this->tax = $tax;
        $this->priceDiscount = $priceDiscount;
        $this->taxDiscount = $taxDiscount;
    }

    public function getPrice($flags = self::BASE)
    {
        $price = $this->basePrice;

        if ($flags & self::WITH_TAX) {
            $price += $this->tax;
        }

        if ($flags & self::WITH_DISCOUNT) {

            $price -= $this->priceDiscount;

            if ($flags & self::WITH_TAX) {
                $price -= $this->taxDiscount;
            }
        }

        return $price;
    }

    private $basePrice;
    private $tax;
    private $priceDiscount;
    private $taxDiscount;
}