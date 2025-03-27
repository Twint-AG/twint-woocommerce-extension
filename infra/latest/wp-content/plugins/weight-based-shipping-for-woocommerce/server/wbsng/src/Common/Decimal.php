<?php declare(strict_types=1);

namespace Gzp\WbsNg\Common;

use Gzp\WbsNg\Common\Equality\Equality;
use Gzp\WbsNg\Common\Equality\Traits\StandardEquality;
use Gzp\WbsNg\Common\Hashing\OrderedHash;
use GzpWbsNgVendors\Brick\Math\BigDecimal;
use GzpWbsNgVendors\Brick\Math\Exception\NumberFormatException;
use InvalidArgumentException;


class Decimal implements Equality
{
    use StandardEquality;


    /**
     * @var self
     */
    public static $zero;

    /**
     * @var self
     */
    public static $one;

    /**
     * @param int|float|string|self $value
     */
    public static function of($value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        if (!is_int($value) && !is_float($value) && !is_string($value) && !($value instanceof BigDecimal)) {
            throw new InvalidArgumentException('unsupported decimal source type');
        }

        if (is_string($value) && !preg_match('/^[+-]?\d*(\.\d*)?$/', $value)) {
            throw new NumberFormatException('not a number');
        }

        $value = BigDecimal::of($value);

        if ($value->isEqualTo(BigDecimal::zero()) && isset(self::$zero)) {
            return self::$zero;
        }
        if ($value->isEqualTo(BigDecimal::one()) && isset(self::$one)) {
            return self::$one;
        }

        return new self($value);
    }

    public static function ofUnscaledValue($value, $scale): self
    {
        return self::of(BigDecimal::ofUnscaledValue($value, $scale));
    }

    public function minus(self $other): self
    {
        return self::of($this->d->minus($other->d));
    }

    public function plus(self $other): self
    {
        return self::of($this->d->plus($other->d));
    }

    public function multipliedBy(self $factor): self
    {
        return self::of($this->d->multipliedBy($factor->d));
    }

    /**
     * @return list<self>
     */
    public function quotientAndRemainder(self $divisor): array
    {
        return array_map([self::class, 'of'], $this->d->quotientAndRemainder($divisor));
    }

    public function isZero(): bool
    {
        return $this->d->isZero();
    }

    public function isPositive(): bool
    {
        return $this->d->isPositive();
    }

    public function isPositiveOrZero(): bool
    {
        return $this->d->isPositiveOrZero();
    }

    public function isLessThan(self $other): bool
    {
        return $this->d->isLessThan($other->d);
    }

    public function isGreaterThanOrEqualTo(self $other): bool
    {
        return $this->d->isGreaterThanOrEqualTo($other->d);
    }

    public function __toString(): string
    {
        return $this->d->__toString();
    }

    public function hash(): int
    {
        /** @noinspection PhpInternalEntityUsedInspection */
        $parts = $this->d->stripTrailingZeros()->__serialize();
        return OrderedHash::from($parts['value'], $parts['scale']);
    }


    protected function _equals(self $to): bool
    {
        return $this->d->isEqualTo($to->d);
    }


    /**
     * @var BigDecimal
     */
    private $d;

    private function __construct(BigDecimal $d)
    {
        $this->d = $d;
    }
}


Decimal::$zero = Decimal::of(0);
Decimal::$one = Decimal::of(1);
