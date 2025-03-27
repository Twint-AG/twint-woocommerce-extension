<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Processing;

use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IRate;
use GzpWbsNgVendors\Dgm\Shengine\Model\Rate;


class RateRegister implements IRate
{
    public $cost;
    public $title;
    public $taxable;
    public $meta = array();

    /**
     * @param IRate|IRate[] $addRates
     */
    public function __construct($addRates = array())
    {
        $this->add($addRates);
    }

    public function toRate()
    {
        return new Rate($this->getCost(), $this->getTitle(), $this->isTaxable(), $this->getMeta());
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

    /**
     * @param IRate|IRate[] $other
     */
    public function add($other)
    {
        $others = is_array($other) ? $other : array($other);

        foreach ($others as $other) {

            $this->rateCount++;

            $this->addMeta($other);

            $this->cost += $other->getCost();

            if (($title = $other->getTitle()) !== null) {
                $this->title = $title;
            }

            if (($taxable = $other->isTaxable()) !== null) {
                $this->taxable = $taxable;
            }
        }
    }


    /** @var int */
    private $rateCount = 0;

    private function addMeta(IRate $other)
    {
        $meta = $other->getMeta();

        switch ($this->rateCount) {
            case 1:
                $this->meta = $meta;
                break;
            /** @noinspection PhpMissingBreakStatementInspection */
            case 2:
                $this->meta = self::formatMeta($this->meta, $this->title, $this->rateCount - 1);
                // no break
            default:
                $this->meta += self::formatMeta($meta, $other->getTitle(), $this->rateCount);
        }
    }


    private static function formatMeta(array $meta, $rateTitle, $rateId)
    {
        foreach ($meta as $key => $value) {

            if (!isset($rateTitle) || $rateTitle === '') {
                $rateTitle = /** @lang text */ '<untitled>';
            }

            $meta["[#{$rateId} {$rateTitle}] {$key}"] = $value;

            unset($meta[$key]);
        }

        return $meta;
    }
}