<?php declare(strict_types=1);

namespace Gzp\WbsNg\Common\Hashing;


class UnorderedHash
{
    /**
     * @param iterable<int> | callable(): iterable<int> $ints
     *
     * https://github.com/openjdk/jdk/blob/959a61fdd483c9523764b9ba0972f59ca06db0ee/src/java.base/share/classes/java/util/AbstractSet.java#L118
     */
    public static function from($ints): int
    {
        if (is_callable($ints)) {
            $ints = $ints();
        }

        $sum = PHP_INT_MIN;
        foreach ($ints as $n) {

            // Make hashes different for an empty collection and a collection having only zero-hash items.
            if ($n === 0) {
                $n = PHP_INT_MAX;
            }

            $sum = self::add($sum, $n);
        }

        return $sum;
    }

    private static function add(int $a, int $b): int
    {
        if ($a >= 0 && $b > ($room = PHP_INT_MAX - $a)) {
            $b -= $room + 1;
            $a = PHP_INT_MIN;
        }

        if ($a < 0 && $b < ($room = PHP_INT_MIN - $a)) {
            $b -= $room - 1;
            $a = PHP_INT_MAX;
        }

        return $a + $b;
    }
}