<?php declare(strict_types=1);

namespace Gzp\WbsNg\Model\Calc;

use Gzp\WbsNg\Common\Decimal;
use Gzp\WbsNg\Common\Equality\Equality;
use Gzp\WbsNg\Common\Equality\Traits\ImmutableHash;
use Gzp\WbsNg\Common\Equality\Traits\StandardEquality;
use Gzp\WbsNg\Common\Hashing\OrderedHash;
use Gzp\WbsNg\Mapping\Context;
use Gzp\WbsNg\Mapping\T;
use Gzp\WbsNg\Model\Order\Bundle;


/**
 * @immutable
 */
class Shipment implements Equality
{
    use StandardEquality;
    use ImmutableHash;
    use ShipmentSerialization;


    /**
     * @var string
     */
    public $title;

    /**
     * @var Decimal
     */
    public $price;

    /**
     * @var Bundle
     */
    public $bundle;

    /**
     * Two shipments using different methods are not equal,
     * even with the same title, price, and bundle.
     *
     * @var int
     */
    public $method;


    public function __construct(string $title, Decimal $price, Bundle $items, int $method)
    {
        assert(!$items->empty(), 'shipment package must not be empty');
        $this->title = $title;
        $this->price = $price;
        $this->bundle = $items;
        $this->method = $method;
    }

    protected function _equals(self $to): bool
    {
        return
            $this->title === $to->title &&
            $this->price->equals($to->price) &&
            $this->bundle->equals($to->bundle) &&
            $this->method === $to->method;
    }

    protected function _hash(): int
    {
        return OrderedHash::from(
            $this->title,
            $this->price,
            $this->bundle,
            $this->method
        );
    }
}


trait ShipmentSerialization
{
    public function serialize(): array
    {
        return [
            'title' => $this->title,
            'price' => $this->price->__toString(),
            'bundle' => $this->bundle->serialize(),
            'method' => $this->method,
        ];
    }

    public static function unserialize(array $data): self
    {
        $data = Context::of($data);
        return new self(
            $data['title']->map([T::class, 'string']),
            $data['price']->map([T::class, 'decimal']),
            $data['bundle']->map([Bundle::class, 'unserialize']),

            // Optional because introduced later.
            // Migrating all existing orders might take much time.
            $data['method']->map([T::class, 'optionalInt']) ?? 0
        );
    }
}