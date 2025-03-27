<?php
namespace  GzpWbsNgVendors\Dgm\Comparator;

use InvalidArgumentException;


abstract class AbstractComparator implements IComparator
{
    public function equal($a, $b)
    {
        return $this->compare($a, $b) == 0;
    }

    public function less($a, $b, $orEqual = false)
    {
        return $this->compare($a, $b) <= ($orEqual ? 0 : -1);
    }

    public function greater($a, $b, $orEqual = false)
    {
        return $this->compare($a, $b) >= ($orEqual ? 0 : 1);
    }

    public function compare($a, $b, $operator = null)
    {
        $cmp = $this->cmp($a, $b);

        if (!isset($operator)) {
            return $cmp;
        }

        $operator = (string)$operator;
        switch ($operator) {
            case '<': return $cmp < 0;
            case '>': return $cmp > 0;
            case '<=': return $cmp <= 0;
            case '>=': return $cmp >= 0;
            case '=':
            case '==': return $cmp == 0;
            default: throw new InvalidArgumentException("Unknown comparison operator '{$operator}'");
        }
    }

    abstract protected function cmp($a, $b);
}