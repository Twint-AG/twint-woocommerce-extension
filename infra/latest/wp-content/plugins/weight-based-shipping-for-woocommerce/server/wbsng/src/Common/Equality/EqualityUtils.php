<?php declare(strict_types=1);

namespace Gzp\WbsNg\Common\Equality;


class EqualityUtils
{
    /**
     * @template T of Equality
     * @param iterable<T> $items
     * @return list<T>
     */
    public static function unique(iterable $items): array
    {
        $map = [];
        foreach ($items as $item) {

            $h = $item->hash();

            foreach ($map[$h] ?? [] as $other) {
                if ($other->equals($item)) {
                    continue 2;
                }
            }

            $map[$h][] = $item;
        }

        if (!$map) {
            return [];
        }

        return array_merge(...$map);
    }
//
//    public static function equalIterable(iterable $a, iterable $b, bool $compareByRef = null): bool
//    {
//        if ($a === $b) {
//            return true;
//        }
//
//        if ((is_array($a) || $a instanceof Countable)
//            && (is_array($b) || $b instanceof Countable)
//            && count($a) !== count($b)) {
//            return false;
//        }
//
//        $aIter = $a instanceof \Iterator ? $a : self::iterable2iterator($a);
//        $bIter = $b instanceof \Iterator ? $b : self::iterable2iterator($b);
//
//        // quickly check if the sets have same items in the same order
//        while ($aIter->valid() && $bIter->valid()) {
//            if ($aIter->current() !== $bIter->current()) {
//                break;
//            }
//            $thisIter->next();
//            $toIter->next();
//        }
//
//    }
//
//    private static function iterable2iterator(iterable $i): Iterator
//    {
//        yield from $i;
//    }
}