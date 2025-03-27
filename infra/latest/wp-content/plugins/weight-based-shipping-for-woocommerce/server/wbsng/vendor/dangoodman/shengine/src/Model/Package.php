<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Model;

use GzpWbsNgVendors\Dgm\Arrays\Arrays;
use GzpWbsNgVendors\Dgm\Shengine\Grouping\NoopGrouping;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\ICondition;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IGrouping;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IItem;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IPackage;


class Package implements IPackage
{
    public function __construct(
        array $items = array(),
        Destination $destination = null,
        Customer $customer = null,
        array $coupons = array(),
        Price $price = null
    ) {
        $this->items = $items;
        $this->destination = $destination;
        $this->customer = $customer;
        $this->coupons = $coupons;
        $this->price = $price;
    }

    /**
     * @param IItem[] $items
     * @param Price|null $price
     * @return self
     */
    public function inherit(array $items, Price $price = null)
    {
        return new self($items, $this->getDestination(), $this->getCustomer(), $this->getCoupons(), $price);
    }

    public function getItems()
    {
        return $this->items;
    }

    public function getPrice($flags = Price::BASE)
    {
        if (isset($this->price)) {
            return $this->price->getPrice($flags);
        }

        $sum = 0;
        foreach ($this->items as $item) {
            $sum += $item->getPrice($flags);
        }

        return $sum;
    }

    public function hasCustomPrice()
    {
        return isset($this->price);
    }

    public function getWeight()
    {
        $weight = 0;
        foreach ($this->getItems() as $item) {
            $weight += $item->getWeight();
        }

        return $weight;
    }

    public function getTerms($taxonomy)
    {
        $terms = Arrays::map($this->getItems(), function (IItem $item) use ($taxonomy) {
            
            $terms = $item->getTerms($taxonomy);
            
            if (!$terms) {
                $terms[] = IPackage::NONE_VIRTUAL_TERM_ID;
            }
            
            $terms = Arrays::map($terms, 'strval');
            
            return $terms;
        });

        $terms = $terms ? array_merge(...$terms) : $terms;

        $terms = array_values(array_unique($terms, SORT_STRING));

        return $terms;
    }

    public function isEmpty()
    {
        return empty($this->items);
    }

    public function getDestination()
    {
        return $this->destination;
    }

    public function getCustomer()
    {
        return $this->customer;
    }

    public function getCoupons()
    {
        return $this->coupons;
    }

    public function splitFilterMerge(IGrouping $splitBy, $filterBy, $requireAllPackages)
    {
        if ($filterBy instanceof ICondition) {
            $filterBy = [$filterBy, 'isSatisfiedBy'];
        }

        $packages = $this->split($splitBy);

        $matchingPackages = array();
        foreach ($packages as $package) {
            if ($filterBy($package)) {
                $matchingPackages[] = $package;
            } else if ($requireAllPackages) {
                return null;
            }
        }

        if (!$matchingPackages) {
            return null;
        }

        if ($matchingPackages === [$this]) {
            return $this;
        }

        $items = self::mergeItems(...Arrays::map($matchingPackages, function(IPackage $pkg) {
            return $pkg->getItems();
        }));

        if ($this->hasCustomPrice()) {

            $theseItems = $this->getItems();

            if ($theseItems === $items) {
                return $this;
            }

            if (count($items) === count($theseItems)) {

                $theseSortedItems = $theseItems;
                usort($theseSortedItems, $cmp = static function ($o1, $o2) {
                    return strcmp(spl_object_hash($o1), spl_object_hash($o2));
                });

                $resultSortedItems = $items;
                usort($resultSortedItems, $cmp);

                if ($theseSortedItems === $resultSortedItems) {
                    return $this;
                }
            }
        }

        return $this->inherit($items);
    }

    public function split(IGrouping $by)
    {
        // Quickly bypass the general way with the same results
        if ($by instanceof NoopGrouping) {
            return [$this];
        }

        $buckets = [];
        {
            $defaultBucket = [];

            foreach ($this->getItems() as $item) {

                $packageIds = $by->getPackageIds($item);

                if (!is_array($packageIds) && empty($packageIds)) {
                    $defaultBucket[] = $item;
                    continue;
                }

                foreach ($packageIds as $bucket) {
                    $buckets[$bucket][] = $item;
                }
            }

            if (!empty($defaultBucket)) {
                $buckets[] = $defaultBucket;
            }
        }

        if (count($buckets) < 2) {
            return [$this];
        }

        $packages = [];
        foreach ($buckets as $items) {
            $packages[] = $this->inherit($items);
        }

        return $packages;
    }

    public function exclude($other)
    {
        if (!is_array($other)) {
            $other = array($other);
        }

        $theseItems = $this->getItems();

        $restItems = array(); {

            foreach ($theseItems as $item) {
                $restItems[spl_object_hash($item)] = $item;
            }

            /** @var IPackage $pkg */
            foreach ($other as $pkg) {
                foreach ($pkg->getItems() as $item) {
                    unset($restItems[spl_object_hash($item)]);
                }
            }
        }

        $package = $this;
        if (count($restItems) < count($theseItems)) {
            $package = new Package(array_values($restItems), $this->getDestination(), $this->getCustomer(), $this->getCoupons());
        }

        return $package;
    }

    /** @var IItem[] */
    private $items;
    private $destination;
    private $customer;
    private $coupons;
    private $price;

    private static function mergeItems(array ...$itemSets)
    {
        $items = array_merge(...$itemSets);

        $mergedItems = [];
        foreach ($items as $item) {
            $mergedItems[spl_object_hash($item)] = $item;
        }

        return array_values($mergedItems);
    }
}
