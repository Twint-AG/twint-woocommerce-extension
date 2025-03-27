<?php declare(strict_types=1);

namespace Gzp\WbsNg\Model\Config;

use Gzp\WbsNg\Mapping\Context;
use Gzp\WbsNg\Mapping\T;
use Gzp\WbsNg\Model\Order\Bundle;
use Gzp\WbsNg\Model\Order\Item;


/**
 * @psalm-immutable
 */
class ShclassCond
{
    use ShclassCondMapping;


    /**
     * @var self
     */
    public static $ANY;

    /**
     * @var self
     */
    public static $NONE;

    public static function of(bool $include, array $shclasses): self
    {
        if (!$shclasses) {
            return $include ? self::$NONE : self::$ANY;
        }

        return new self($include, $shclasses);
    }

    /**
     * @param list<string> $shclasses
     */
    public function __construct(bool $include, array $shclasses)
    {
        $this->include = $include;
        $this->shclasses = $shclasses;
    }

    public function match(Bundle $items): Bundle
    {
        // fast path
        if (isset($this->shclasses) && !$this->shclasses) {
            return $this->include ? Bundle::$EMPTY : $items;
        }

        if (isset($this->shclasses)) {
            $this->shclassedFlipped = array_flip($this->shclasses);
            unset($this->shclasses);
        }

        return $items->filter(function(Item $item) {
            $isset = isset($this->shclassedFlipped[$item->shclass]);
            return $this->include ? $isset : !$isset;
        });
    }

    /**
     * @var bool
     */
    private $include;

    /**
     * @var list<string>
     */
    private $shclasses;

    /**
     * @var array<int, mixed>
     */
    private $shclassedFlipped;
}


ShclassCond::$ANY = new ShclassCond(false, []);
ShclassCond::$NONE = new ShclassCond(true, []);


trait ShclassCondMapping
{
    public static function unserialize(?array $data): self
    {
        if (!isset($data)) {
            return self::$ANY;
        }

        $data = Context::of($data);

        $include = $data['include']->map([T::class, 'bool']);
        $items = $data['items']->map([T::class, 'array']);

        return self::of($include, $items);
    }
}