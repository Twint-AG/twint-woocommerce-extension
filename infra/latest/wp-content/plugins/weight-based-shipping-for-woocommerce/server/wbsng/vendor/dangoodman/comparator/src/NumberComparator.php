<?php
namespace GzpWbsNgVendors\Dgm\Comparator;

use InvalidArgumentException;


/**
 * @property-read number|null $precision
 */
class NumberComparator extends AbstractComparator
{
    public function __construct($precision = null)
    {
        if (isset($precision) && $precision == 0) {
            throw new InvalidArgumentException('Comparing numbers with zero precision is meaningless.');
        }

        $this->precision = $precision;
    }

    public function __get($property)
    {
        if ($property === 'precision') {
            return $this->{$property};
        }

        return null;
    }

    public function __isset($property)
    {
        if ($property === 'precision') {
            return isset($this->{$property});
        }

        return false;
    }


    protected function cmp($a, $b)
    {
        $a = $this->normalize($a);
        $b = $this->normalize($b);
        $cmp = ($a == $b) ? 0 : ($a < $b ? -1 : 1);
        return $cmp;
    }

    protected function normalize($value)
    {
        if (!is_numeric($value)) {
            $type = gettype($value);
            throw new InvalidArgumentException(
                "Number comparator expects numeric values to be compared, value '{$value}' of type '{$type}' given.");
        }

        $value = is_int($value) ? $value : (float)$value;

        if (isset($this->precision)) {
            $value = round($value * $this->precision) / $this->precision;
        }

        return $value;
    }


    private $precision;
}