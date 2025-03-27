<?php declare(strict_types=1);

namespace Gzp\WbsNg\Model\Order;


use Gzp\WbsNg\Common\Decimal;
use GzpWbsNgVendors\Dgm\Shengine\Attributes\ProductVariationAttribute;
use GzpWbsNgVendors\Dgm\Shengine\Grouping\AttributeGrouping;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IItem;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IItemAggregatables;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IPackage;
use GzpWbsNgVendors\Dgm\Shengine\Model\Destination;
use GzpWbsNgVendors\Dgm\Shengine\Model\Price as ShenginePrice;


class Convert
{
    /**
     * @param IPackage $shenginePackage
     * @return list{Bundle, ?Destination}
     * @noinspection PhpDocSignatureInspection
     */
    public static function convert(IPackage $shenginePackage): array
    {
        $lines = [];
        $shengineLinePackages = $shenginePackage->split(new AttributeGrouping(new ProductVariationAttribute()));
        foreach ($shengineLinePackages as $shengineLinePackage) {

            $shengineItems = $shengineLinePackage->getItems();
            if (!$shengineItems) {
                continue;
            }

            $shengineItem = $shengineItems[0];

            $id = (int)($shengineItem->getProductVariationId() ?? $shengineItem->getProductId());
            $wcp = wc_get_product($id);
            $title = $wcp instanceof \WC_Product ? $wcp->get_title() : "#$id";

            // TODO: support decimal quantities for quantity condition and bundle weight, price, etc.
            $quantity = count($shengineItems);

            // no float-point error expected on weight-quantity re-multiplying
            $weight = Decimal::of($shengineItem->getWeight() * $quantity);

            $price = self::price($shengineItem, $quantity);

            $shclass = (int)($shengineItem->getTerms(IItem::TAXONOMY_SHIPPING_CLASS)[0] ?? -1);

            $lines[] = new Item($id, $title, $quantity, $weight, $price, $shclass);
        }

        $bundle = new Bundle(
            $lines,
            $shenginePackage->hasCustomPrice() ? self::price($shenginePackage) : null
        );

        $dest = $shenginePackage->getDestination();

        return [$bundle, $dest];
    }

    private static function price(IItemAggregatables $priceSource, $quantity = 1): Price
    {
        return new Price(
            Decimal::of($priceSource->getPrice() * $quantity),
            Decimal::of($priceSource->getPrice(ShenginePrice::WITH_TAX) * $quantity),
            Decimal::of($priceSource->getPrice(ShenginePrice::WITH_DISCOUNT) * $quantity),
            Decimal::of($priceSource->getPrice(ShenginePrice::WITH_TAX | ShenginePrice::WITH_DISCOUNT) * $quantity)
        );
    }
}