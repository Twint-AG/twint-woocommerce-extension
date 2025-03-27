<?php
namespace Wbs;

use WbsVendors\Dgm\Arrays\Arrays;
use WbsVendors\Dgm\NumberUnit\NumberUnit;
use WbsVendors\Dgm\Range\Range;
use WbsVendors\Dgm\Shengine\Aggregators\SumAggregator;
use WbsVendors\Dgm\Shengine\Attributes\DestinationAttribute;
use WbsVendors\Dgm\Shengine\Attributes\ItemAttribute;
use WbsVendors\Dgm\Shengine\Attributes\PriceAttribute;
use WbsVendors\Dgm\Shengine\Attributes\WeightAttribute;
use WbsVendors\Dgm\Shengine\Calculators\AggregatedCalculator;
use WbsVendors\Dgm\Shengine\Calculators\ChildrenCalculator;
use WbsVendors\Dgm\Shengine\Calculators\ClampCalculator;
use WbsVendors\Dgm\Shengine\Calculators\ConstantCalculator;
use WbsVendors\Dgm\Shengine\Calculators\FreeCalculator;
use WbsVendors\Dgm\Shengine\Calculators\GroupCalculator;
use WbsVendors\Dgm\Shengine\Calculators\ProgressiveCalculator;
use WbsVendors\Dgm\Shengine\Conditions\Common\Compare\BetweenCondition;
use WbsVendors\Dgm\Shengine\Conditions\Common\Logic\AndCondition;
use WbsVendors\Dgm\Shengine\Conditions\Common\Logic\NotCondition;
use WbsVendors\Dgm\Shengine\Conditions\Common\Logic\OrCondition;
use WbsVendors\Dgm\Shengine\Conditions\DestinationCondition;
use WbsVendors\Dgm\Shengine\Conditions\Package\PackageAttributeCondition;
use WbsVendors\Dgm\Shengine\Conditions\Package\TermsCondition;
use WbsVendors\Dgm\Shengine\Grouping\AttributeGrouping;
use WbsVendors\Dgm\Shengine\Grouping\NoopGrouping;
use WbsVendors\Dgm\Shengine\Interfaces\IAttribute;
use WbsVendors\Dgm\Shengine\Interfaces\ICalculator;
use WbsVendors\Dgm\Shengine\Interfaces\IItemAggregatables;
use WbsVendors\Dgm\Shengine\Interfaces\IProcessor;
use WbsVendors\Dgm\Shengine\Model\Price;
use WbsVendors\Dgm\Shengine\Model\Rule;
use WbsVendors\Dgm\Shengine\Model\RuleMeta;
use WbsVendors\Dgm\Shengine\RuleMatcher;
use WbsVendors\Dgm\Shengine\RuleMatcherMeta;
use WbsVendors\Dgm\Shengine\Units;


class RulesMapper
{
    public function __construct(Units $units, IProcessor $processor)
    {
        $this->units = $units;
        $this->processor = $processor;
    }

    public function read($_rules)
    {
        $rules = array();

        if (!isset($_rules)) {
            return $rules;
        }

        $rules = array();
        foreach ($_rules as $_rule) {
            if ($rule = $this->makeRule($_rule)) {
                $rules[] = $rule;
            }
        }

        return $rules;
    }


    static private function get($object, $path, $default = null)
    {
        foreach (explode('.', $path) as $step) {

            if (!isset($object[$step])) {
                $object = null;
                break;
            }

            $object = $object[$step];
        }

        if (!isset($object)) {
            $object = $default;
        }

        return $object;
    }


    private $units;
    private $processor;

    private function makeRule(array $_rule)
    {
        $rule = null;

        $_meta = self::get($_rule, 'meta');

        if (self::get($_meta, 'enabled', true)) {
            $rule = new Rule(
                new RuleMeta(
                    self::get($_meta, 'title'),
                    self::get($_meta, 'taxable')
                ),
                new RuleMatcher(
                    new RuleMatcherMeta(false, new NoopGrouping(), false),
                    $this->makeCondition($_rule)
                ),
                $this->makeChargeCalculator($_rule)
            );
        }

        return $rule;
    }

    private function makeChargeCalculator(array $_rule)
    {
        $_charges = self::get($_rule, 'charges');
        if (!$_charges) {
            return new FreeCalculator();
        }

        $perClassRules = array();
        $perClassConditions = array();
        $perClassMatcherMeta = new RuleMatcherMeta(false, new AttributeGrouping(new ItemAttribute()));

        $_classCharges = self::get($_charges, 'shippingClasses', array());
        foreach ($_classCharges as $_classCharge) {

            $_class = self::get($_classCharge, 'shippingClass');
            if (!isset($_class)) {
                continue;
            }

            $_chrg = self::get($_classCharge, 'charges');

            $condition = new TermsCondition(array(IItemAggregatables::TAXONOMY_SHIPPING_CLASS => array($_class)));
            $perClassConditions[] = $condition;

            $perClassRules[] = new Rule(
                new RuleMeta(),
                new RuleMatcher(
                    $perClassMatcherMeta,
                    $condition
                ),
                new AggregatedCalculator(
                    new GroupCalculator(array(
                        new ConstantCalculator(self::get($_chrg, 'base', 0)),
                        $this->makeWeightCalculator(self::get($_chrg, 'weight'))
                    )),
                    new SumAggregator()
                )
            );
        }

        $defaultClassRule = new Rule(
            new RuleMeta(),
            new RuleMatcher(
                $perClassMatcherMeta,
                new NotCondition(new OrCondition($perClassConditions))
            ),
            $this->makeWeightCalculator(self::get($_charges, 'weight'))
        );

        $perClassRules[] = $defaultClassRule;

        $charge = new AggregatedCalculator(
            new GroupCalculator(array(
                new ConstantCalculator(self::get($_charges, 'base', 0)),
                new ChildrenCalculator($this->processor, $perClassRules),
            )),
            new SumAggregator()
        );

        $charge = $this->applyModifiers($charge, $_rule);

        return $charge;
    }

    private function applyModifiers(ICalculator $charge, array $_rule)
    {
        $_range = self::get($_rule, 'modifiers.clamp.range', null);
        if (isset($_range)) {
            $charge = new ClampCalculator($charge, $this->readRange($_range));
        }

        return $charge;
    }

    private function makeCondition(array $_rule)
    {
        return new AndCondition($this->makeConditions($_rule));
    }

    private function makeConditions(array $_rule)
    {
        $conditions = array();

        if ($_conditions = self::get($_rule, 'conditions')) {
            $conditions = Arrays::filter(array(
                $this->makeDestinationCondition($_conditions),
                $this->makeWeightCondition($_conditions),
                $this->makePriceCondition($_conditions),
            ));
        }

        return $conditions;
    }

    private function makeWeightCalculator(array $_weight)
    {
        return new ProgressiveCalculator(
            new WeightAttribute(),
            $this->units->weight,
            self::get($_weight, 'cost', 0),
            self::get($_weight, 'step', 1),
            self::get($_weight, 'skip', 0)
        );
    }

    private function makeDestinationCondition(array $_conditions)
    {
        $condition = null;

        $_destination = self::get($_conditions, 'destination');
        $_mode = self::get($_destination, 'mode', 'include');
        $_locations = self::get($_destination, 'locations', array());

        if ($_destination && $_mode !== 'all') {

            $condition = new DestinationCondition($_locations);

            if ($_mode === 'exclude') {
                $condition = new NotCondition($condition);
            }

            $condition = new PackageAttributeCondition(
                $condition,
                new DestinationAttribute()
            );
        }

        return $condition;
    }

    private function makeWeightCondition(array $_conditions)
    {
        $condition = null;

        if ($_weight = self::get($_conditions, 'weight.range')) {
            $condition = $this->makeRangeCondition(
                $_weight,
                $this->units->weight,
                new WeightAttribute()
            );
        }

        return $condition;
    }

    private function makePriceCondition(array $_conditions)
    {
        $condition = null;

        if ($_subtotal = self::get($_conditions, 'subtotal.range')) {
            $condition = $this->makeRangeCondition(
                $_subtotal,
                $this->units->price,
                new PriceAttribute(
                    Price::BASE |
                    (self::get($_conditions, 'subtotal.tax', false) ? Price::WITH_TAX : 0) |
                    (self::get($_conditions, 'subtotal.discount', true) ? Price::WITH_DISCOUNT : 0)
                )
            );
        }

        return $condition;
    }


    private function makeRangeCondition(array $_range, NumberUnit $unit, IAttribute $attribute)
    {
        return new PackageAttributeCondition(
            new BetweenCondition($this->readRange($_range), $unit),
            $attribute
        );
    }

    private function readRange(array $_range)
    {
        return new Range(
            self::get($_range, 'min'),
            self::get($_range, 'max'),
            self::get($_range, 'minInclusive', true),
            self::get($_range, 'maxInclusive', true)
        );
    }
}