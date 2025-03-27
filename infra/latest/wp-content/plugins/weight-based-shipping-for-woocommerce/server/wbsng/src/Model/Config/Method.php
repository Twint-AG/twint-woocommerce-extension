<?php declare(strict_types=1);

namespace Gzp\WbsNg\Model\Config;

use Gzp\WbsNg\Common\Decimal;
use Gzp\WbsNg\Mapping\Context;
use Gzp\WbsNg\Mapping\T;
use Gzp\WbsNg\Model\Calc\Shipment;
use Gzp\WbsNg\Model\Config\Method\Settings;
use Gzp\WbsNg\Model\Order\Bundle;
use GzpWbsNgVendors\Dgm\Shengine\Model\Destination;
use Iterator;
use Traversable;


class Method
{
    use MethodMapping;


    /**
     * @var bool
     */
    public $disabled = false;

    /**
     * @var string
     */
    public $name;

    /**
     * @var \IteratorAggregate<mixed, Rule>
     */
    public $rules;

    /**
     * @var Settings
     */
    public $settings;


    /**
     * @param \IteratorAggregate<mixed, Rule>|list<Rule> $rules
     */
    public function __construct(string $name, $rules, Settings $settings = null)
    {
        $this->name = $name;
        $this->rules = is_array($rules) ? new IteratorAggregateFrom($rules) : $rules;
        $this->settings = $settings ?? new Settings();
    }

    public function apply(Bundle $items, ?Destination $dest, int $methodId): ?Shipment
    {
        $shrinks = 0;

        start:

        $shipment = $this->_apply($items, $dest, $methodId);

        if ($shipment && $shipment->bundle->count() < $items->count()) {
            $items = $shipment->bundle;
            $shrinks++;
            goto start;
        }

        if ($shrinks > 1 && function_exists('wc_get_logger')) {
            wc_get_logger()->warning("method bundle shrunk more than once; shrinks=$shrinks, method=$this->name");
        }

        return $shipment;
    }

    public function active(): bool
    {
        return !$this->disabled && !$this->empty();
    }

    public function empty(): bool
    {
        /** @noinspection PhpLoopNeverIteratesInspection */
        foreach ($this->rules as $ignored) {
            return false;
        }
        return true;
    }

    private function _apply(Bundle $items, ?Destination $dest, int $methodId): ?Shipment
    {
        if ($this->disabled) {
            return null;
        }

        $charge = Decimal::$zero;

        $effectiveTitle = null;
        $distinctTitles = 0;

        $unmatched = $items;
        $next = $items;

        $rulesMatched = 0;
        foreach ($this->rules as $rule) {

            $rate = $rule->rate($next, $dest, $this->settings->price);
            if (!isset($rate)) {
                return null;
            }
            if ($rate->matched->empty()) {
                continue;
            }

            $rulesMatched++;

            $charge = $charge->plus($rate->price);

            $ruleTitle = $rule->name === '' ? $this->name : $rule->name;
            if ($distinctTitles < 2 && $ruleTitle !== $effectiveTitle) {
                $effectiveTitle = $ruleTitle;
                $distinctTitles++;
            }

            $unmatched = $unmatched
                ->exclude($rate->matched)
                ->exclude($rate->dropped);

            $next = $next->exclude($rate->dropped);
            if ($next->empty()) {
                break;
            }
        }

        if (!$rulesMatched) {
            return null;
        }

        if ($distinctTitles !== 1) {
            $effectiveTitle = $this->name;
        }

        return new Shipment($effectiveTitle, $charge, $items->exclude($unmatched), $methodId);
    }
}


trait MethodMapping
{
    public static function unserialize(array $data): self
    {
        $data = Context::of($data);

        $disabled = $data['disabled']->map([T::class, 'optionalBool'], false);

        $title = $data['name']->map([T::class, 'nonWhitespace']);

        $rules = new CachingIterator(function() use($data) {
            foreach ($data['rules'] as $r) {
                $rule = $r->map([Rule::class, 'unserialize']);
                if (isset($rule)) {
                    yield $rule;
                }
            }
        });

        $settings = $data['settings']->map([Settings::class, 'unserialize']);

        $self = new self($title, $rules, $settings);

        $self->disabled = $disabled;

        return $self;
    }
}


class CachingIterator implements \IteratorAggregate
{
    /**
     * @param Iterator|callable(): Iterator $items
     */
    public function __construct(callable $items)
    {
        if (is_callable($items)) {
            $items = $items();
        }
        $this->iterator = $items;
    }

    public function getIterator(): Traversable
    {
        $idx = 0;
        $iter = $this->iterator;

        while (true) {

            if ($idx === count($this->pairs)) {

                if ($this->firstFetch) {
                    $this->firstFetch = false;
                }
                else {
                    $iter->next();
                }

                if (!$iter->valid()) {
                    return;
                }

                $key = $iter->key();
                $value = $iter->current();

                $this->pairs[] = [$key, $value];
            }

            [$k, $v] = $this->pairs[$idx];
            $idx++;

            yield $k => $v;
        }
    }

    /**
     * @var Iterator
     */
    private $iterator;

    /**
     * @var bool
     */
    private $firstFetch = true;

    /**
     * @var list<list{array-key, mixed}>
     */
    private $pairs = [];
}


class IteratorAggregateFrom implements \IteratorAggregate
{
    /**
     * @param array|callable(): \Generator $source
     */
    public function __construct($source)
    {
        $this->source = $source;
    }

    public function getIterator(): Traversable
    {
        /** @var iterable $iterable */
        $iterable = is_callable($this->source) ? ($this->source)() : $this->source;
        yield from $iterable;
    }


    /**
     * @var array|\Generator
     */
    private $source;
}