<?php declare(strict_types=1);
namespace Gzp\WbsNg\Model\Config;

use Gzp\WbsNg\Common\Decimal;
use Gzp\WbsNg\Mapping\Context;
use Gzp\WbsNg\Mapping\Exceptions\Invalid;
use Gzp\WbsNg\Mapping\T;
use Gzp\WbsNg\Model\Config\Method\PriceSettings;
use Gzp\WbsNg\Model\Order\Bundle;
use GzpWbsNgVendors\Dgm\Shengine\Model\Destination;


/**
 * @property-read string $name
 * @property-read DestCond $locations
 * @property-read ShclassCond $shclasses
 * @property-read Range $weight
 * @property-read Range $price
 * @property-read Charge $charge
 * @property-read ?Action $action
 *
 * @immutable
 */
class Rule
{
    use RuleMapping;


    public function rate(Bundle $items, ?Destination $dest, PriceSettings $priceSettings): ?Result
    {
        $empty = Result::empty($items);

        if (!$this->locations->match($dest)) {
            return $empty;
        }

        $matched = $this->shclasses->match($items);
        if ($matched->empty()) {
            return $empty;
        }

        if (!$this->weight->includes($matched->weight())) {
            return $empty;
        }

        $price = $matched->price()->get($priceSettings->afterTaxes, $priceSettings->afterDiscounts);
        if (!$this->price->includes($price)) {
            return $empty;
        }

        if (!$this->action) {
            return null;
        }

        $drop = $matched; // optimization
        if ($this->action->drop !== $this->shclasses) {
            $drop = $this->action->drop->match($items);
        }

        return new Result(
            $this->charge->calc($matched),
            $matched,
            $drop
        );
    }
}


class Result
{
    /**
     * @var Decimal
     */
    public $price;

    /**
     * @var Bundle
     */
    public $matched;

    /**
     * @var Bundle
     */
    public $dropped;

    public static function empty(Bundle $dropped): self
    {
        return new self(Decimal::$zero, Bundle::$EMPTY, $dropped);
    }

    public function __construct(Decimal $price, Bundle $matched, Bundle $dropped)
    {
        $this->price = $price;
        $this->matched = $matched;
        $this->dropped = $dropped;
    }
}


class Action
{
    /**
     * @var ShclassCond
     */
    public $drop;

    public function __construct(ShclassCond $drop = null)
    {
        $this->drop = $drop ?? ShclassCond::$NONE;
    }
}


trait RuleMapping
{
    public static function unserialize(array $data): ?self
    {
        $data = Context::of($data);

        $disabled = $data['disabled']->map([T::class, 'optionalBool'], false);
        if ($disabled) {
            return null;
        }

        $rule = new self();
        $rule->ctx = $data;
        return $rule;
    }

    /**
     * @throws Invalid
     */
    public function __get(string $prop)
    {
        if (!isset($this->props[$prop])) {
            $this->props[$prop] = $this->init($prop);
        }

        return $this->props[$prop];
    }

    /**
     * @var Context
     */
    private $ctx;

    private $props = [];

    /**
     * @throws Invalid If the config processing failed
     * @noinspection PhpDocRedundantThrowsInspection
     */
    private function init(string $prop)
    {
        switch ($prop) {

            case 'name':
                return $this->ctx['name']->map([T::class, 'optionalString'], '');

            case 'locations':
                return $this->ctx['locations']->map([DestCond::class, 'unserialize']);

            case 'shclasses':
                return $this->ctx['shclasses']->map([ShclassCond::class, 'unserialize']);

            case 'weight':
            case 'price':
                return $this->ctx[$prop]->map([Range::class, 'unserialize']);

            case 'charge':
                return $this->ctx['charge']->map([Charge::class, 'unserialize']);

            case 'action':
                return $this->ctx['action']->map(function($x) {
                    return $this->mapAction($x);
                });

            default:
                throw new \LogicException("unknown field $prop");
        }
    }

    private function mapAction(?array $action): ?Action
    {
        if ($action === null) {
            return new Action(ShclassCond::$NONE);
        }

        $action = Context::of($action);

        return $action['type']->map(function(string $v) use ($action) {
            switch ($v) {

                case 'drop':
                    $shclasses = $action['items']->map(function($x) {
                        return $x === null
                            ? $this->shclasses
                            : Context::of($x)->map([ShclassCond::class, 'unserialize']);
                    });
                    return new Action($shclasses);

                case 'finish':
                    return new Action(ShclassCond::$ANY);

                case 'cancel':
                    return null;

                default:
                    throw new Invalid('unsupported action type');
            }
        });
    }
}