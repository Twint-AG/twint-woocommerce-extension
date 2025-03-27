<?php declare(strict_types=1);

namespace Gzp\WbsNg\Model\Order;

use Gzp\WbsNg\Common\Decimal;
use Gzp\WbsNg\Common\Equality\Equality;
use Gzp\WbsNg\Common\Equality\Traits\StandardEquality;
use Gzp\WbsNg\Mapping\Context;
use Gzp\WbsNg\Mapping\T;


/**
 * @immutable
 */
class Item implements Equality
{
    use StandardEquality;
    use ItemSerialization;


    const NONE_VIRTUAL_TERM_ID = -1;

    /**
     * @var string
     */
    public $name;

    /**
     * @var int
     */
    public $quantity;

    /**
     * @var Decimal
     */
    public $weight;

    /**
     * @var Price
     */
    public $price;

    /**
     * It should be replaced with a ShclassRef object once we need more than the class id.
     *
     * @var int
     */
    public $shclass;


    public function __construct(int $id, string $name = null, int $quantity = null, Decimal $weight = null, Price $price = null, int $shclass = null)
    {
        $this->id = $id;
        $this->name = $name ?? '';
        $this->quantity = $quantity ?? 1;
        $this->weight = $weight ?? Decimal::$zero;
        $this->price = $price ?? Price::$ZERO;
        $this->shclass = $shclass ?? self::NONE_VIRTUAL_TERM_ID;
    }

    public function hash(): int
    {
        return $this->id;
    }

    protected function _equals(self $to): bool
    {
        return $this->id === $to->id;
    }

    /**
     * @var int
     */
    private $id;
}


trait ItemSerialization
{
    public function serialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'quantity' => $this->quantity,
            'weight' => $this->weight->__toString(),
            'price' => $this->price->serialize(),
            'shclass' => $this->shclass,
        ];
    }

    public static function unserialize(array $data): self
    {
        $data = Context::of($data);
        return new self(
            $data['id']->map([T::class, 'int']),
            $data['name']->map([T::class, 'string']),
            $data['quantity']->map([T::class, 'int']),
            $data['weight']->map([T::class, 'decimal']),
            $data['price']->map([Price::class, 'unserialize']),
            $data['shclass']->map([T::class, 'int'])
        );
    }
}