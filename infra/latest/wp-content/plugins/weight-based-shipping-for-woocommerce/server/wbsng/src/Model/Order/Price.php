<?php declare(strict_types=1);

namespace Gzp\WbsNg\Model\Order;

use Gzp\WbsNg\Common\Decimal;
use Gzp\WbsNg\Common\Equality\Equality;
use Gzp\WbsNg\Common\Equality\Traits\ImmutableHash;
use Gzp\WbsNg\Common\Equality\Traits\StandardEquality;
use Gzp\WbsNg\Common\Hashing\OrderedHash;
use Gzp\WbsNg\Mapping\Context;
use Gzp\WbsNg\Mapping\T;


/**
 * @immutable
 */
class Price implements Equality
{
    use ImmutableHash;
    use StandardEquality;
    use PriceSerialization;


    /**
     * @var self
     */
    public static $ZERO;

//    public static function of(Decimal $base, Decimal $tax, Decimal $priceDiscount, Decimal $taxDiscount): self
//    {
//        return new self($base, $tax, $priceDiscount, $taxDiscount);
//    }

//    public static function of(Decimal $base, Decimal $tax, Decimal $priceDiscount, Decimal $taxDiscount): self
//    {
//        if ($base->isZero() && $tax->isZero() && $priceDiscount->isZero() && $taxDiscount->isZero()) {
//            if (!isset(self::$ZERO)) {
//                self::$ZERO = new self($base, $tax, $priceDiscount, $taxDiscount);
//            }
//            return self::$ZERO;
//        }
//
//        return new self($base, $tax, $priceDiscount, $taxDiscount);
//    }

    public static function fromWc(Decimal $base, Decimal $tax = null, Decimal $baseDiscount = null, Decimal $taxDiscount = null): self
    {
        $tax = $tax ?? Decimal::$zero;
        $baseDiscount = $baseDiscount ?? Decimal::$zero;
        $taxDiscount = $taxDiscount ?? Decimal::$zero;

        return new self(
            $base,
            $base->plus($tax),
            $base->minus($baseDiscount),
            $base->plus($tax)->minus($baseDiscount)->minus($taxDiscount)
        );
    }

    /**
     * @return list{Decimal, Decimal, Decimal, Decimal}
     * @noinspection PhpDocSignatureInspection
     */
    public function toWc(): array
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        return [
            $base = $this->base,
            $tax = $this->withTax->minus($base),
            $baseDiscount = $base->minus($this->withDiscount),
            $taxDiscount = $base->plus($tax)->minus($baseDiscount)->minus($this->withTaxAndDiscount),
        ];
    }

    public function __construct(Decimal $base, Decimal $withTax = null, Decimal $withDiscount = null, Decimal $withTaxAndDiscount = null)
    {
        $this->base = $base;
        $this->withTax = $withTax ?? $base;
        $this->withDiscount = $withDiscount ?? $base;
        $this->withTaxAndDiscount = $withTaxAndDiscount ?? $base;
    }

    public function add(self $other): self
    {
        return new self(
            $this->base->plus($other->base),
            $this->withTax->plus($other->withTax),
            $this->withDiscount->plus($other->withDiscount),
            $this->withTaxAndDiscount->plus($other->withTaxAndDiscount)
        );
    }

    /**
     * @noinspection RedundantElseClauseInspection
     * @noinspection NestedPositiveIfStatementsInspection
     */
    public function get(bool $tax = false, bool $discount = false): Decimal
    {
        if ($tax) {
            if ($discount) {
                return $this->withTaxAndDiscount;
            }
            else {
                return $this->withTax;
            }
        }
        else {
            if ($discount) {
                return $this->withDiscount;
            }
            else {
                return $this->base;
            }
        }
    }

    protected function _equals($to): bool
    {
        return
            $this->base->equals($to->base) &&
            $this->withTax->equals($to->withTax) &&
            $this->withDiscount->equals($to->withDiscount) &&
            $this->withTaxAndDiscount->equals($to->withTaxAndDiscount);
    }

    protected function _hash(): int
    {
        return OrderedHash::from(
            $this->base,
            $this->withTax,
            $this->withDiscount,
            $this->withTaxAndDiscount
        );
    }


    private $base;
    private $withTax;
    private $withDiscount;
    private $withTaxAndDiscount;
}


Price::$ZERO = new Price(Decimal::$zero, Decimal::$zero, Decimal::$zero, Decimal::$zero);


trait PriceSerialization
{
    public function serialize(): array
    {
        $parts = $this->toWc();

        $fields = [];
        foreach (self::$fields as $i => $f) {
            $v = $parts[$i];
            if ($i === 0 || !$v->isZero()) {
                $fields[$f] = $v->__toString();
            }
        }

        return $fields;
    }

    public static function unserialize(array $data): self
    {
        $data = Context::of($data);

        $parts = [];
        foreach (self::$fields as $i => $f) {
            if ($i === 0) {
                $parts[] = $data[$f]->map([T::class, 'decimal']);
            }
            else {
                $parts[] = $data[$f]->map([T::class, 'optionalDecimal'], Decimal::$zero);
            }
        }

        return self::fromWc($parts[0], $parts[1], $parts[2], $parts[3]);
    }

    /**
     * @readonly
     * @var list<string>
     */
    private static $fields = ['base', 'tax', 'discount', 'taxDiscount'];
}