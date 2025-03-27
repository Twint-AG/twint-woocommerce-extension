<?php
namespace GzpWbsNgVendors\Dgm\NumberUnit;

use GzpWbsNgVendors\Dgm\Comparator\NumberComparator;


class NumberUnit extends NumberComparator
{
    /** @var self */
    static $ASIS;

    /** @var self */
    static $INT;


	/**
	 * Returns how many chunks of $chunk size are in the $value.
	 * Roughly, ceil($value / $chunk).
	 *
	 * @param number $value
	 * @param number $chunk
	 * @return int
	 */
    public function chunks($value, $chunk)
    {
        $chunk = $this->normalize($chunk);
        if ($chunk == 0) {
            throw new \InvalidArgumentException("Chunk size cannot be zero.");
        }

	    return (int)ceil($this->normalize($value) / $chunk);
    }
}

NumberUnit::$ASIS = new NumberUnit(null);
NumberUnit::$INT = new NumberUnit(1);
