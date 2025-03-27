<?php declare(strict_types=1);

namespace Gzp\WbsNg\Common;

use Countable;
use Gzp\WbsNg\Common\Equality\Equality;
use Gzp\WbsNg\Common\Equality\Traits\StandardEquality;
use Gzp\WbsNg\Common\Hashing\UnorderedHash;
use IteratorAggregate;
use Traversable;


/**
 * A set of objects preserving the insertion order. The order is ignored on comparison.
 *
 * Adding, deleting or querying a non-object will lead to a fatal error.
 *
 * IHashEquals::equals() is used to check the equality if available for either item; otherwise – ===.
 * IHashEquals::hash() is used if an item implements it; otherwise – spl_object_id.
 * The custom item hash must never change while the item is in the set.
 *
 * SplObjectStorage was used initially as a backend. But it is not possible to implement IteratorAggregate on top of it,
 * which is required for proper nested and parallel iteration.
 *
 * @template T of object
 * @implements IteratorAggregate<int, T>
 */
class Set implements IteratorAggregate, Countable, Equality
{
    use StandardEquality;

    /**
     * @param iterable<T> $items
     */
    public function __construct(iterable $items = [])
    {
        $this->add(...$items);
    }

    /**
     * @param T ...$items
     */
    public function add(object ...$items): void
    {
        foreach ($items as $item) {

            $hash = $this->itemHash($item);

            if (empty($this->buckets[$hash])) {
                $this->buckets[$hash] = [];
            }

            $bucket = &$this->buckets[$hash];
            foreach ($bucket as $bitem) {
                if ($this->itemEquals($item, $bitem)) {
                    continue 2;
                }
            }

            $this->count++;
            $this->items[$this->id] = $item;
            $bucket[$this->id] = $item;
            $this->id++;
        }
    }

    /**
     * @param T ...$items
     */
    public function delete(object ...$items): bool
    {
        // fast path
        if ($this->empty()) {
            return false;
        }

        $before = $this->count;

        foreach ($items as $item) {

            $hash = $this->itemHash($item);

            if (empty($this->buckets[$hash])) {
                continue;
            }

            $bucket = &$this->buckets[$hash];
            foreach ($bucket as $id => $bitem) {
                if ($this->itemEquals($item, $bitem)) {

                    $this->count--;

                    unset($this->items[$id]);

                    unset($bucket[$id]);
                    if (empty($bucket)) {
                        unset($this->buckets[$hash]);
                    }

                    break;
                }
            }
        }

        return $this->count !== $before;
    }

    /**
     * @param T $item
     */
    public function has(object $item): bool
    {
        $hash = $this->itemHash($item);

        $bucket = $this->buckets[$hash] ?? null;
        if ($bucket === null) {
            return false;
        }

        foreach ($bucket as $bitem) {
            if ($this->itemEquals($item, $bitem)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param T $item
     * @return self<T>
     */
    public function with(object $item): self
    {
        $c = clone($this);
        $c->add($item);
        return $c;
    }

    /**
     * @param T $item
     * @return self<T>
     */
    public function without(object $item): self
    {
        $c = clone($this);
        $c->delete($item);
        return $c;
    }

    public function count(): int
    {
        return $this->count;
    }

    public function empty(): bool
    {
        return $this->count === 0;
    }

    /**
     * @param callable(T, T): int $by
     */
    public function sort(callable $by): void
    {
        uasort($this->items, $by);
    }

    public function clone(): self
    {
        return clone($this);
    }

    /**
     * @return Traversable<int, T>
     */
    public function getIterator(): Traversable
    {
        foreach ($this->items as $item) {
            yield $item;
        }
    }

    public function hash(): int
    {
        return UnorderedHash::from(function() {
            foreach ($this->buckets as $hash => $bucket) {
                $c = count($bucket);
                while ($c--) {
                    yield $hash;
                }
            }
        });
    }


    protected function _equals(self $to): bool
    {
        if ($this->count() !== $to->count()) {
            return false;
        }

        foreach ($this as $item) {
            if (!$to->has($item)) {
                return false;
            }
        }

        return true;
    }


    /**
     * @var array<int, array<int, T>>
     */
    private $buckets = [];

    /**
     * @var array<int, T>
     */
    private $items = [];

    /**
     * @var int
     */
    private $id = 0;

    /**
     * @var int
     */
    private $count = 0;

    /**
     * @param T $a
     * @param T $b
     */
    private function itemEquals(object $a, object $b): bool
    {
        if ($a instanceof Equality) {
            return $a->equals($b);
        }
        if ($b instanceof Equality) {
            return $b->equals($a);
        }
        return $a === $b;
    }

    /**
     * @param T $item
     */
    private function itemHash(object $item): int
    {
        if ($item instanceof Equality) {
            return $item->hash();
        }
        return spl_object_id($item);
    }
}