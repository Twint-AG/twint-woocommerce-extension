<?php declare(strict_types=1);

namespace Gzp\WbsNg\Common;


/**
 * @template K
 * @template V
 * @implements \IteratorAggregate<K, V>
 * @implements \ArrayAccess<K, V>
 */
class Map implements \IteratorAggregate, \ArrayAccess, \Countable
{
    public function __construct()
    {
        $this->m = new \SplObjectStorage();
    }

    /**
     * @param K $key
     * @param V $value
     */
    public function set($key, $value): void
    {
        $this->m->attach($key, $value);
    }

    /**
     * @param K $key
     * @return ?V
     */
    public function get($key)
    {
        return $this->m[$key] ?? null;
    }

    /**
     * @param K $key
     */
    public function remove($key): void
    {
        $this->m->detach($key);
    }

    public function has($key): bool
    {
        return $this->m->contains($key);
    }

    public function empty(): bool
    {
        return $this->m->count() === 0;
    }

    /**
     * @param callable(K, K): int $by
     */
    public function sortKeys(callable $by): void
    {
        $pairs = [];
        foreach ($this as $k => $v) {
            $pairs[] = [$k, $v];
        }

        usort($pairs, function($pair1, $pair2) use($by) {
            return $by($pair1[0], $pair2[0]);
        });

        $this->m = new \SplObjectStorage();
        foreach ($pairs as [$k, $v]) {
            $this->set($k, $v);
        }
    }

    public function merge(self $other): void
    {
        foreach ($other as $k => $v) {
            $this[$k] = $v;
        }
    }

    /**
     * Updates the map inplace with the new values provided by the passed callback.
     *
     * @template NV
     * @param callable(V): NV $f
     * @return void
     */
    public function apply(callable $f): void
    {
        foreach ($this as $k => $v) {
            $nv = $f($v);
            if ($nv !== $v) {
                $this->set($k, $nv);
            }
        }
    }

    public function count(): int
    {
        return $this->m->count();
    }

    public function keys(): \Iterator
    {
        return $this->m;
    }

    public function getIterator()
    {
        foreach ($this->m as $k) {
            yield $k => $this->m[$k];
        }
    }

    public function offsetExists($key): bool
    {
        return $this->m->offsetExists($key);
    }

    public function offsetGet($key)
    {
        if (!$this->offsetExists($key)) {
            return null;
        }
        return $this->m->offsetGet($key);
    }

    public function offsetSet($key, $value): void
    {
        $this->m->offsetSet($key, $value);
    }

    public function offsetUnset($key): void
    {
        $this->m->offsetUnset($key);
    }


    /**
     * @var \SplObjectStorage<K, V>
     */
    private $m;
}