<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Conditions\Package;

use GzpWbsNgVendors\Dgm\Shengine\Grouping\ByItemGrouping;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\ICondition;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IPackage;
use Dgm\Shengine\Model\Package;
use InvalidArgumentException;


class TermsCondition extends AbstractPackageCondition
{
    const SEARCH_ANY = 'search_any';
    const SEARCH_ALL = 'search_all';
    const SEARCH_NO = 'search_no';


    public function __construct($needleTermsByTaxonomy, $search = self::SEARCH_ANY, $allowOthers = true, ICondition $matchingItemsConstraint = null)
    {
        if (!in_array((string)$search, array(self::SEARCH_ANY, self::SEARCH_ALL, self::SEARCH_NO), true)) {
            throw new InvalidArgumentException("Unknown search mode '{$search}'");
        }

        $this->needleTermsByTaxonomy = self::receiveTermsByTaxonomy($needleTermsByTaxonomy);
        $this->searchMode = $search;
        $this->allowOthers = $allowOthers;
        $this->matchingItemsConstraint = $matchingItemsConstraint;
    }

    protected function isSatisfiedByPackage(IPackage $package)
    {
        /* // Eliminated purposely
        if (!$this->needleTermsByTaxonomy) {
            return true;
        }*/

        $match = $this->searchMode !== self::SEARCH_ANY;

        foreach ($this->needleTermsByTaxonomy as $taxonomy => $needle) {

            $haystack = $package->getTerms($taxonomy);

            $intersections = count(array_intersect($needle, $haystack));

            switch ($this->searchMode) {
                case self::SEARCH_ANY:
                    $match = $match || $intersections > 0;
                    break;
                case self::SEARCH_ALL:
                    $match = $match && $intersections === count($needle);
                    break;
                case self::SEARCH_NO:
                    $match = $match && $intersections === 0;
                    break;
            }

            if ($this->allowOthers) {
                if ($match === ($this->searchMode === self::SEARCH_ANY)) {
                    break;
                }
            } elseif ($intersections !== count($haystack)) {
                $match = false;
                break;
            }
        }

        if ($match && isset($this->matchingItemsConstraint)) {

            $matchingPackage = $package->splitFilterMerge(
                ByItemGrouping::instance(),
                function(IPackage $pkg) {
                    foreach ($this->needleTermsByTaxonomy as $taxonomy => $searchTerms) {
                        if (count(array_intersect($searchTerms, $pkg->getTerms($taxonomy)))) {
                            return true;
                        }
                    }
                    return false;
                },
                false
            );

            $match = $this->matchingItemsConstraint->isSatisfiedBy($matchingPackage);
        }

        return $match;
    }

    
    private $needleTermsByTaxonomy;
    private $searchMode;
    private $allowOthers;
    private $matchingItemsConstraint;

    private static function normalize(array $terms)
    {
        return array_unique($terms);
    }

    private static function receiveTermsByTaxonomy(array $input)
    {
        foreach ($input as $taxonomy => &$terms) {

            if (!is_array($terms)) {
                $type = gettype($terms);
                throw new InvalidArgumentException("term list must be an array, {$type} given");
            }

            if (!$terms) {
                unset($input[$taxonomy]);
            } else {
                $terms = self::normalize($terms);
            }
        }

        return $input; 
    }
}
