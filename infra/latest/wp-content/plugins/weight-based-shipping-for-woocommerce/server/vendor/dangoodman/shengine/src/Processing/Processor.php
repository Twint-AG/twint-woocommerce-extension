<?php
namespace WbsVendors\Dgm\Shengine\Processing;

use Dgm\Arrays\Arrays;
use Dgm\Shengine\Interfaces\IPackage;
use Dgm\Shengine\Interfaces\IProcessor;
use Dgm\Shengine\Interfaces\IRate;
use Dgm\Shengine\Interfaces\IRule;
use Dgm\Shengine\Model\Rate;


class Processor implements \WbsVendors\Dgm\Shengine\Interfaces\IProcessor
{
    public function process($rules, \WbsVendors\Dgm\Shengine\Interfaces\IPackage $package)
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

        return \WbsVendors\Dgm\Arrays\Arrays::map($rates, function (\WbsVendors\Dgm\Shengine\Interfaces\IRate $rate) use ($title, $taxable) {

            if ($title !== null && $rate->getTitle() === null) {
                $rate = new \WbsVendors\Dgm\Shengine\Model\Rate($rate->getCost(), $title, $rate->isTaxable());
            }

            if ($taxable !== null && $rate->isTaxable() === null) {
                $rate = new \WbsVendors\Dgm\Shengine\Model\Rate($rate->getCost(), $rate->getTitle(), $taxable);
            }

            return $rate;
        });
    }
}