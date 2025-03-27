<?php
namespace GzpWbsNgVendors\Dgm\Comparator;

use InvalidArgumentException;


class StringComparator extends AbstractComparator
{
    protected function cmp($a, $b)
    {
        $a = $this->normalize($a);
        $b = $this->normalize($b);
        return strcmp($a, $b);
    }

    private function normalize($value)
    {
        if (!is_scalar($value)) {
            $type = gettype($value);
            throw new InvalidArgumentException(
                "String comparator expects scalars to be compared as strings, '{$type}' given.");
        }

        return (string)$value;
    }
}