<?php declare(strict_types=1);

namespace Gzp\WbsNg\Common;


/**
 * @template K
 * @template V
 * @template U
 * @param iterable<K, V> $items
 * @param callable(V): U $f
 * @return array<K, U>
 */
function map(iterable $items, callable $f): array
{
    $res = [];
    foreach ($items as $k => $item) {
        $res[$k] = $f($item);
    }
    return $res;
}

function filter(iterable $items, callable $f): array
{
    $res = [];
    foreach ($items as $item) {
        if ($f($item)) {
            $res[] = $item;
        }
    }
    return $res;
}

function any(iterable $items, callable $f): bool
{
    foreach ($items as $item) {
        if ($f($item)) {
            return true;
        }
    }

    return false;
}

function every(iterable $items, callable $f): bool
{
    foreach ($items as $item) {
        if (!$f($item)) {
            return false;
        }
    }

    return true;
}