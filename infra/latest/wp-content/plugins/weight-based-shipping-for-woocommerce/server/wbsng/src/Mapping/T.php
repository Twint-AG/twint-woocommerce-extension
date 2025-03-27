<?php declare(strict_types=1);

namespace Gzp\WbsNg\Mapping;

use Gzp\WbsNg\Common\Decimal;
use Gzp\WbsNg\Mapping\Exceptions\Invalid;
use Gzp\WbsNg\Mapping\Exceptions\InvalidType;
use Gzp\WbsNg\Mapping\Exceptions\Required;
use GzpWbsNgVendors\Brick\Math\Exception\NumberFormatException;


class T
{
    /**
     * @throws Invalid
     */
    public static function bool($v): bool
    {
        return self::type('boolean', $v);
    }

    /**
     * @throws Invalid
     */
    public static function optionalBool($v, bool $default = null): ?bool
    {
        return self::type('boolean', $v, true) ?? $default;
    }

    /**
     * @throws Invalid
     */
    public static function int($v): int
    {
        return self::type('integer', $v);
    }

    /**
     * @throws Invalid
     */
    public static function optionalInt($v, int $default = null): ?int
    {
        return self::type('integer', $v, true) ?? $default;
    }

    /**
     * @throws Invalid
     */
    public static function string($v): string
    {
        return self::type('string', $v);
    }

    /**
     * @throws Invalid
     */
    public static function optionalString($v, string $default = null): ?string
    {
        return self::type('string', $v, true) ?? $default;
    }

    /**
     * @throws Invalid
     */
    public static function nonWhitespace($v): string
    {
        $v = self::type('string', $v);

        $v = trim($v);
        if ($v === '') {
            throw new Invalid("a non-whitespace string expected");
        }

        return $v;
    }

    /**
     * @throws Invalid
     */
    public static function array($v): array
    {
        return self::type('array', $v);
    }

    /**
     * @throws Required
     * @throws Invalid
     */
    public static function decimal($v): Decimal
    {
        $v = self::optionalDecimal($v);
        self::required($v);
        return $v;
    }

    /**
     * @throws Invalid
     */
    public static function optionalDecimal($v, Decimal $default = null): ?Decimal
    {
        $v = self::optionalString($v);
        if (!isset($v)) {
            return $default;
        }

        try {
            return Decimal::of($v);
        }
        catch (NumberFormatException $e) {
            throw new Invalid($e->getMessage(), 0, $e);
        }
    }

    /**
     * @return mixed
     * @throws Invalid
     */
    private static function type(string $type, $v, bool $optional = false)
    {
        if (!isset($v) && $optional) {
            return null;
        }

        self::required($v);

        $t = gettype($v);
        if ($t !== $type) {
            throw new InvalidType($type, $t);
        }

        return $v;
    }

    /**
     * @throws Required
     */
    private static function required($v): void
    {
        if (!isset($v)) {
            throw new Required();
        }
    }
}