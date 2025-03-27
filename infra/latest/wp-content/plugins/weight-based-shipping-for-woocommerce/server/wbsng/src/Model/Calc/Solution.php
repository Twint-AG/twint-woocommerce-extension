<?php declare(strict_types=1);

namespace Gzp\WbsNg\Model\Calc;

use Gzp\WbsNg\Common\Decimal;
use Gzp\WbsNg\Common\Equality\Equality;
use Gzp\WbsNg\Common\Equality\Traits\ImmutableHash;
use Gzp\WbsNg\Common\Equality\Traits\StandardEquality;
use Gzp\WbsNg\Common\Hashing\UnorderedHash;
use Gzp\WbsNg\Common\Set;
use Gzp\WbsNg\Mapping\Context;


/**
 * @immutable
 */
class Solution implements Equality
{
    use StandardEquality;
    use ImmutableHash;
    use SolutionSerialization;


    /**
     * @var Set<Shipment>
     */
    public $shipments;

    /**
     * computed
     * @var Decimal
     */
    public $price;

    /**
     * computed
     * @var string
     */
    public $title;


    /**
     * @param Set<Shipment> $shipments
     */
    public function __construct(Set $shipments)
    {
        assert(!$shipments->empty(), 'solution must not be empty');

        $this->shipments = $shipments;

        $price = Decimal::$zero;
        $titles = [];
        foreach ($shipments as $s) {
            $price = $price->plus($s->price);
            $titles[] = $s->title;
        }

        $this->price = $price;
        $this->title = join(' + ', $titles);
    }

    protected function _equals(self $to): bool
    {
        return $this->shipments->equals($to->shipments);
    }

    protected function _hash(): int
    {
        return UnorderedHash::from(function() {
            foreach ($this->shipments as $shipment) {
                yield $shipment->hash();
            }
        });
    }
}


trait SolutionSerialization
{
    public function serialize(): array
    {
        $shipments = [];
        foreach ($this->shipments as $x) {
            $shipments[] = $x->serialize();
        }

        return [
            'shipments' => $shipments
        ];
    }

    public static function unserialize(array $data): self
    {
        $data = Context::of($data);

        $shipments = [];
        foreach ($data['shipments'] as $datum) {
            $shipments[] = $datum->map([Shipment::class, 'unserialize']);
        }

        return new self(new Set($shipments));
    }
}