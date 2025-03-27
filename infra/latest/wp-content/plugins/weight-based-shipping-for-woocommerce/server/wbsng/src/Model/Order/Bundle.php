<?php declare(strict_types=1);

namespace Gzp\WbsNg\Model\Order;

use Countable;
use Gzp\WbsNg\Common\Decimal;
use Gzp\WbsNg\Common\Equality\Equality;
use Gzp\WbsNg\Common\Equality\Traits\ImmutableHash;
use Gzp\WbsNg\Common\Equality\Traits\StandardEquality;
use Gzp\WbsNg\Common\Hashing\OrderedHash;
use Gzp\WbsNg\Common\Set;
use Gzp\WbsNg\Mapping\Context;
use IteratorAggregate;
use Traversable;


/**
 * @immutable
 * @implements IteratorAggregate<int, Item>
 */
class Bundle implements Equality, Countable, IteratorAggregate
{
    use StandardEquality;
    use ImmutableHash;
    use BundleSerialization;


    /**
     * @var self
     */
    public static $EMPTY;

    /**
     * @param Set<Item>|iterable<mixed, Item> $items Caller must not modify the set after construction.
     */
    public function __construct($items = [], Price $priceOverride = null)
    {
        if (!$items instanceof Set) {
            $items = new Set($items);
        }
        $this->items = $items;

        $this->price = $priceOverride;
        $this->priceOverridden = $priceOverride !== null;
    }

    public function price(): Price
    {
        if (!isset($this->price)) {
            $price = Price::$ZERO;
            foreach ($this->items as $item) {
                $price = $price->add($item->price);
            }
            $this->price = $price;
        }

        return $this->price;
    }

    public function weight(): Decimal
    {
        if (!isset($this->weight)) {
            $this->weight = Decimal::$zero;
            foreach ($this->items as $item) {
                $this->weight = $this->weight->plus($item->weight);
            }
        }

        return $this->weight;
    }

    /**
     * @param iterable<mixed, Item> $exclude
     * @return self
     */
    public function exclude(iterable $exclude): self
    {
        // fast path
        if ($this->items->empty()
            || is_array($exclude) && empty($exclude)
            || $exclude instanceof Countable && $exclude->count() === 0) {
            return $this;
        }

        // fast path
        if ($exclude === $this
            || $exclude === $this->items) {
            return $this->priceOverridden ? new self([], $this->price) : self::$EMPTY;
        }

        $newItems = $this->items->clone();
        if (!$newItems->delete(...$exclude)) {
            return $this;
        }

        return new self($newItems);
    }

    /**
     * @param callable(Item): bool $f
     * @return self
     */
    public function filter(callable $f): self
    {
        $drop = [];
        foreach ($this->items as $item) {
            if (!$f($item)) {
                $drop[] = $item;
            }
        }

        // fast path
        if (count($drop) === $this->items->count()) {
            return self::$EMPTY;
        }

        return $this->exclude($drop);
    }

    public function empty(): bool
    {
        return $this->items->empty();
    }

    public function count(): int
    {
        return $this->items->count();
    }

    /**
     * @return Traversable<int, Item>
     */
    public function getIterator(): Traversable
    {
        return $this->items;
    }

    protected function _equals(self $to): bool
    {
        return (
            self::nullableEqual($this->price(), $to->price())
            && $this->items->equals($to->items)
        );
    }

    protected function _hash(): int
    {
        return OrderedHash::from(
            $this->price(),
            $this->items->hash()
        );
    }

    /**
     * @var Set<Item>
     */
    private $items;

    /**
     * @var ?Price
     */
    private $price;

    /**
     * @var bool
     */
    private $priceOverridden;

    /**
     * @var Decimal
     */
    private $weight;

}


Bundle::$EMPTY = new Bundle([]);


trait BundleSerialization
{
    public function serialize(): array
    {
        $items = [];
        foreach ($this->items as $x) {
            $items[] = $x->serialize();
        }
        $data = ['items' => $items];

        if ($this->priceOverridden) {
            $data['price'] = $this->price->serialize();
        }

        return $data;
    }

    public static function unserialize(array $data): self
    {
        $data = Context::of($data);

        $items = [];
        foreach ($data['items'] as $item) {
            $items[] = $item->map([Item::class, 'unserialize']);
        }

        $price = $data['price']->map(function($x) {
            return $x === null ? null : Price::unserialize($x);
        });

        return new self($items, $price);
    }
}