<?php declare(strict_types=1);

namespace Gzp\WbsNg\Model\Config;

use Gzp\WbsNg\Common\Decimal;
use Gzp\WbsNg\Mapping\Context;
use Gzp\WbsNg\Mapping\T;


/**
 * @psalm-immutable
 */
class Range
{
    use RangeMapping;


    /**
     * @readonly
     * @var ?Decimal
     */
    public $min;

    /**
     * @readonly
     * @var ?Decimal
     */
    public $max;


    public function __construct(?Decimal $min, ?Decimal $max)
    {
        $this->min = $min;
        $this->max = $max;
    }

    public function includes(Decimal $value): bool
    {
        return
            (!isset($this->min) || $value->isGreaterThanOrEqualTo($this->min)) &&
            (!isset($this->max) || $value->isLessThan($this->max));
    }
}


trait RangeMapping
{
    public static function unserialize(?array $data): self
    {
        if (!isset($data)) {
            self::$noop = new self(null, null);
            return self::$noop;
        }

        $data = Context::of($data);

        return new self(
            $data['min']->map([T::class, 'optionalDecimal']),
            $data['max']->map([T::class, 'optionalDecimal'])
        );
    }

    private static $noop;
}