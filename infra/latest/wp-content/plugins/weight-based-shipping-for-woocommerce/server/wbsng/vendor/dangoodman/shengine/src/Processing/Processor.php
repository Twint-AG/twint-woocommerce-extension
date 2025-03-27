<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Processing;

use GzpWbsNgVendors\Dgm\Arrays\Arrays;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IPackage;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IProcessor;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IRate;
use Dgm\Shengine\Interfaces\IRule;
use GzpWbsNgVendors\Dgm\Shengine\Model\Rate;


class Processor implements IProcessor
{
    public function process($rules, IPackage $package)
    {
        $allRates = array();

        foreach ($rules as $rule) {
            /** @var IRule $rule */

            $matcher = $rule->getMatcher();
            $matchingPackage = $matcher->getMatchingPackage($package);

            if (isset($matchingPackage)) {

                $rates = $rule->getCalculator()->calculateRatesFor($matchingPackage);

                $ruleMeta = $rule->getMeta();
                $rates = $this->assign($rates, $ruleMeta->getTitle(), $ruleMeta->isTaxable());

                $allRates = array_merge($allRates, $rates);

                if ($matcher->isCapturingMatcher()) {

                    $package = $package->exclude($matchingPackage);

                    if ($package->isEmpty()) {
                        break;
                    }
                }
            }
        }

        return $allRates;
    }

    private function assign(array $rates, $title, $taxable)
    {
        if (!isset($title) && !isset($taxable)) {
            return $rates;
        }

        return Arrays::map($rates, function (IRate $rate) use ($title, $taxable) {

            if ($title !== null && $rate->getTitle() === null) {
                $rate = new Rate($rate->getCost(), $title, $rate->isTaxable());
            }

            if ($taxable !== null && $rate->isTaxable() === null) {
                $rate = new Rate($rate->getCost(), $rate->getTitle(), $taxable);
            }

            return $rate;
        });
    }
}