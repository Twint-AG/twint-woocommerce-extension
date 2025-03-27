<?php declare(strict_types=1);

namespace Gzp\WbsNg\Common\Equality\Traits;

use Gzp\WbsNg\Common\Equality\Equality;


trait StandardEquality
{
    public function equals($to): bool
    {
        if ($this === $to) {
            return true;
        }
        if (!isset($to) || get_class($this) !== get_class($to)) {
            return false;
        }

        $equals = $this->_equals($to);

        assert($this->_equals($this), 'equality must be reflexive');
        assert($to->_equals($this) === $equals, 'equality must be symmetric, e.g., a->equals(b) === b->equals(a)');
        assert(!$equals || $this->hash() === $to->hash(), 'hashes of equal objects must be equal too');

        return $equals;
    }

    abstract protected function _equals(self $to): bool;

    private static function nullableEqual(?Equality $a, ?Equality $b): bool
    {
        if (isset($a)) {
            return $a->equals($b);
        }
        if (isset($b)) {
            return $b->equals($a);
        }
        return true;
    }
}