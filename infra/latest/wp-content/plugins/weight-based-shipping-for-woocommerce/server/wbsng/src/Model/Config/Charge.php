<?php declare(strict_types=1);
namespace Gzp\WbsNg\Model\Config;

use Gzp\WbsNg\Common\Decimal;
use Gzp\WbsNg\Mapping\Context;
use Gzp\WbsNg\Mapping\T;
use Gzp\WbsNg\Model\Order\Bundle;


/**
 * @immutable
 */
class Charge
{
    use ChargeMapping;


    /**
     * @var Decimal
     */
    public $base;

    /**
     * @var Decimal
     */
    public $rate;

    /**
     * @var Decimal
     */
    public $step;

    /**
     * @var Decimal
     */
    public $skip;


    public function __construct(Decimal $base, Decimal $rate, Decimal $step, Decimal $skip)
    {
        $this->base = $base;
        $this->rate = $rate;
        $this->step = $step;
        $this->skip = $skip;
    }

    public function calc(Bundle $items): Decimal
    {
        $price = $this->base;

        $weight = $items->weight()->minus($this->skip);
        if ($weight->isPositive()) {
            if ($this->step->isZero()) {
                $price = $price->plus($weight->multipliedBy($this->rate));
            }
            else {

                [$chunks, $r] = $weight->quotientAndRemainder($this->step);
                if (!$r->isZero()) {
                    $chunks = $chunks->plus(Decimal::of($chunks->isPositiveOrZero() ? 1 : -1));
                }

                $price = $price->plus($chunks->multipliedBy($this->rate));
            }
        }

        return $price;
    }

    public function free(): bool
    {
        return $this->base->isZero() && $this->rate->isZero();
    }

    /**
     * @var self
     */
    private static $free;
}


trait ChargeMapping
{
    public static function unserialize(?array $data): self
    {
        if ($data === null) {
            if (!isset(self::$free)) {
                $o = Decimal::$zero;
                self::$free = new self($o, $o, $o, $o);
            }
            return self::$free;
        }

        $data = Context::of($data);
        return new self(
            $data['base']->map([T::class, 'decimal']),
            $data['rate']->map([T::class, 'decimal']),
            $data['step']->map([T::class, 'decimal']),
            $data['skip']->map([T::class, 'decimal'])
        );
    }
}