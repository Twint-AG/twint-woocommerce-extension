<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Woocommerce\Converters;

use GzpWbsNgVendors\Deferred\Deferred;
use GzpWbsNgVendors\Dgm\Shengine\Attributes\ProductVariationAttribute;
use GzpWbsNgVendors\Dgm\Shengine\Grouping\AttributeGrouping;
use Dgm\Shengine\Interfaces\IItem;
use GzpWbsNgVendors\Dgm\Shengine\Interfaces\IPackage;
use GzpWbsNgVendors\Dgm\Shengine\Model\Address;
use GzpWbsNgVendors\Dgm\Shengine\Model\Customer;
use GzpWbsNgVendors\Dgm\Shengine\Model\Destination;
use GzpWbsNgVendors\Dgm\Shengine\Model\Dimensions;
use GzpWbsNgVendors\Dgm\Shengine\Model\Package;
use GzpWbsNgVendors\Dgm\Shengine\Model\Price;
use GzpWbsNgVendors\Dgm\Shengine\Woocommerce\Model\Item\WoocommerceItem;
use GzpWbsNgVendors\Dgm\Shengine\Woocommerce\Model\Item\WpmlAwareItem;
use InvalidArgumentException;
use SebastianBergmann\ObjectReflector\TestFixture\ChildClass;
use WC_Cart;
use WC_Product;
use WC_Product_Variation;


class PackageConverter
{
    /**
     * @param IPackage $package
     * @return array
     */
    public static function fromCoreToWoocommerce(IPackage $package)
    {
        $wcpkg = array();
        $wcpkg['contents'] = self::makeWcItems($package);
        $wcpkg['contents_cost'] = self::calcContentsCostField($wcpkg['contents']);
        $wcpkg['applied_coupons'] = $package->getCoupons();
        $wcpkg['user']['ID'] = self::getCustomerId($package);
        $wcpkg['destination'] = self::getDestination($package);
        return $wcpkg;
    }

    /**
     * @param array $_package
     * @param WC_Cart|null $cart  If passed and $_package is the entire order, the returned package will contain
     *                            non-shippable (virtual or others) items as well. Set to null to cancel that behavior.
     * @return IPackage
     * @deprecated {@see fromWoocommerceToCore2}
     */
    public static function fromWoocommerceToCore(array $_package, WC_Cart $cart = null)
    {
        return self::fromWoocommerceToCore2($_package, $cart, false, isset($cart));
    }

    /**
     * @param array $_package
     * @param WC_Cart $cart
     * @param bool $preferCustomPackagePriceOverPerItemPrices Requires $cart to be set
     * @param bool $includeVirtualItemsIfPackageIsRoot Requires $cart to be set
     * @return IPackage
     */
    public static function fromWoocommerceToCore2(
        array $_package,
        WC_Cart $cart = null,
        $preferCustomPackagePriceOverPerItemPrices = false,
        $includeVirtualItemsIfPackageIsRoot = false
    ) {
        if (($preferCustomPackagePriceOverPerItemPrices || $includeVirtualItemsIfPackageIsRoot) && !isset($cart)) {
            throw new InvalidArgumentException('$cart is required for extended options, null given');
        }

        $skipNonShippableItems = true;
        if ($includeVirtualItemsIfPackageIsRoot && self::isGlobalPackage($_package, $cart)) {

            add_filter($fltr = 'woocommerce_product_needs_shipping', $fltrcb = '__return_true', $fltpr = PHP_INT_MAX, 0);
            $deferred = new Deferred(function() use($fltr, $fltrcb, $fltpr) {
                remove_filter($fltr, $fltrcb, $fltpr);
            });

            $globalPackages = $cart->get_shipping_packages();

            unset($deferred);

            $_package = reset($globalPackages);
            $skipNonShippableItems = false;
        }

        $items = array();

        foreach ((array)@$_package['contents'] as $_item) {

            /** @var WC_Product $product */
            $product = $_item['data'];
            if ($skipNonShippableItems && !$product->needs_shipping()) {
                continue;
            }

            $quantity = null;
            $weightFactor = 1;
            {
                $quantity = $_item['quantity'];
                if (!is_numeric($quantity)) {
                    self::error("Invalid quantity '{$quantity}' (not a number) for product #{$_item['id']}.");
                    continue;
                }

                $quantity = self::isConvertibleToInt($quantity) ? (int)$quantity : (float)$quantity;
                if ($quantity <= 0) {
                    if ($quantity < 0) {
                        self::error("Invalid quantity '{$quantity}' (negative number) for product #{$_item['id']}.");
                    }
                    continue;
                }

                if (is_float($quantity) || self::supportsFractionalQuantity($product)) {
                    $weightFactor = $quantity;
                    $quantity = 1;
                }
            }

            // line_subtotal = base price
            // line_total = base price with discount
            // line_subtotal_tax = tax for base price
            // line_tax = tax for base price with discounts
            $price = new Price(
                $_item['line_subtotal'] / $quantity,
                $_item['line_subtotal_tax'] / $quantity,
                ($_item['line_subtotal'] - $_item['line_total']) / $quantity,
                ($_item['line_subtotal_tax'] - $_item['line_tax']) / $quantity
            );

            $variationAttributes = array();
            foreach ((@$_item['variation'] ?: array()) as $attr => $value) {
                if (substr_compare($attr, 'attribute_', 0, 10) === 0) {
                    $variationAttributes[substr($attr, 10)] = $value;
                }
            }
            
            while ($quantity--) {
                $item = new WpmlAwareItem(
                    (string)self::getProductAttr($product, 'id'),
                    self::getProductAttr($product, 'variation_id'),
                    $price,
                    (float)$product->get_weight() * $weightFactor,
                    self::getDimensions($product)
                );
                $item->setOriginalProductObject($product);
                $item->setVariationAttributes($variationAttributes);
                $items[] = $item;
            }
        }

        $destination = null;
        if (($dest = @$_package['destination']) && @$dest['country']) {

            $destination = new Destination(
                $dest['country'],
                @$dest['state'],
                @$dest['postcode'],
                @$dest['city'],
                new Address(@$dest['address'], @$dest['address_2'])
            );
        }

        $customer = null;
        if (isset($_package['user']['ID'])) {
            $customer = new Customer($_package['user']['ID']);
        }

        $coupons = array();
        if (!empty($_package['applied_coupons'])) {
            $coupons = array_map('strtolower', $_package['applied_coupons']);
        }

        $customPackagePrice = null;
        if ($preferCustomPackagePriceOverPerItemPrices && self::isGlobalPackage($_package, $cart)) {
            $customPackagePrice = new Price(
                (float)$cart->get_subtotal(),
                (float)$cart->get_subtotal_tax(),
                (float)$cart->get_discount_total(),
                (float)$cart->get_discount_tax()
            );
        }

        return new Package($items, $destination, $customer, $coupons, $customPackagePrice);
    }

    private static function isGlobalPackage($_package, WC_Cart $cart)
    {
        $globalPackages = $cart->get_shipping_packages();
        return count($globalPackages) === 1 && self::comparePackages(reset($globalPackages), $_package);
    }

    /** @noinspection IfReturnReturnSimplificationInspection */
    private static function comparePackages(array $package1, array $package2)
    {
        unset($package1['rates'], $package2['rates']);
        if ($package1 === $package2) {
            return true;
        }

        ksort($package1);
        ksort($package2);
        if ($package1 === $package2) {
            return true;
        }

        return false;
    }

    private static function makeWcItems(IPackage $package)
    {
        $wcItems = array();

        $lineGrouping = new AttributeGrouping(new ProductVariationAttribute());
        $lines = $package->split($lineGrouping);

        foreach ($lines as $line) {

            $items = $line->getItems();
            if (!$items) {
                continue;
            }

            /** @var IItem $item */
            $item = reset($items);

            $product = null; {

                if ($item instanceof WoocommerceItem) {
                    /** @var WoocommerceItem $item */
                    $product = $item->getOriginalProductObject();
                }

                if (!isset($product)) {

                    $productPostId = $item->getProductVariationId();
                    if (!isset($productPostId)) {
                        $productPostId = $item->getProductId();
                    }

                    $product = wc_get_product($productPostId);
                }
            }

            $wcItem = array(); {

                $wcItem['data'] = $product;
                $wcItem['data_hash'] = wc_get_cart_item_data_hash($product);
                $wcItem['quantity'] = count($items);

                $wcItem['product_id'] = self::getProductAttr($product, 'id');
                $wcItem['variation_id'] = (int)self::getProductAttr($product, 'variation_id');
                $wcItem['variation'] = ($product instanceof WC_Product_Variation) ? $product->get_variation_attributes() : [];

                $wcItem['line_total'] = $line->getPrice(Price::WITH_DISCOUNT);
                $wcItem['line_tax'] = $line->getPrice(Price::WITH_DISCOUNT | Price::WITH_TAX) - $wcItem['line_total'];
                $wcItem['line_subtotal'] = $line->getPrice(Price::BASE);
                $wcItem['line_subtotal_tax'] = $line->getPrice(Price::WITH_TAX) - $wcItem['line_subtotal'];
            }

            // We don't want to have a cart instance dependency just to generate line id. generate_cart_id() method
            // is a static method both conceptually and actually, i.e. it does not (should not) depend on actual
            // cart instance. So we'd rather call it statically.
            /** @noinspection PhpUnhandledExceptionInspection */
            /** @var WC_Cart $cart */
            $cart = (new \ReflectionClass(WC_Cart::class))->newInstanceWithoutConstructor();
            $wcItemId = $cart->generate_cart_id($wcItem['product_id'], $wcItem['variation_id'], $wcItem['variation']);


            $wcItem['key'] = $wcItemId;
            $wcItems[$wcItemId] = $wcItem;
        }

        return $wcItems;
    }

    private static function calcContentsCostField($wcItems)
    {
        $value = 0;

        foreach ($wcItems as $item) {

            /** @var WC_Product $product */
            $product = $item['data'];

            if ($product->needs_shipping() && isset($item['line_total'])) {
                $value += $item['line_total'];
            }
        }

        return $value;
    }

    private static function getCustomerId(IPackage $package)
    {
        if ($customer = $package->getCustomer()) {
            return $customer->getId();
        }

        return null;
    }

    private static function getDestination(IPackage $package)
    {
        if ($destination = $package->getDestination()) {

            $address = $destination->getAddress();

            return array_map('strval', array(
                'country' => $destination->getCountry(),
                'state' => $destination->getState(),
                'postcode' => $destination->getPostalCode(),
                'city' => $destination->getCity(),
                'address' => $address ? $address->getLine1() : null,
                'address_1' => $address ? $address->getLine1() : null,
                'address_2' => $address ? $address->getLine2() : null,
            ));
        }

        return null;
    }

    private static function getDimensions(WC_Product $product)
    {
        return new Dimensions(
            (float)self::getProductAttr($product, 'length'),
            (float)self::getProductAttr($product, 'width'),
            (float)self::getProductAttr($product, 'height')
        );
    }

    private static function getProductAttr(WC_Product $product, $attr)
    {
        if (version_compare(WC()->version, '3.0', '>=')) {
            switch ((string)$attr) {

                case 'id':
                    return $product->is_type('variation') ? $product->get_parent_id() : $product->get_id();

                case 'variation_id':
                    return $product->is_type('variation') ? $product->get_id() : null;

                default:
                    return call_user_func(array($product, "get_{$attr}"));
            }
        }

        return $product->{$attr};
    }

    /**
     * Checks whether a value can be converted to int without loosing precision.
     *
     * isConvertibleToInt("1.0") => true
     * isConvertibleToInt(1.0) => true
     * isConvertibleToInt(1e5) => true
     * isConvertibleToInt(1.5) => false
     * isConvertibleToInt([]) => false
     * isConvertibleToInt(PHP_INT_MAX+1) => false
     * isConvertibleToInt(1e10) => fale
     *
     * @param mixed $value
     * @return bool
     */
    private static function isConvertibleToInt($value)
    {
        return is_numeric($value) && (int)$value == (float)$value;
    }

    /**
     * @param WC_Product $product
     * @return bool
     */
    private static function supportsFractionalQuantity(WC_Product $product)
    {
        return
            !self::isConvertibleToInt(apply_filters('woocommerce_quantity_input_max', 0, $product)) ||
            !self::isConvertibleToInt(apply_filters('woocommerce_quantity_input_max', -1, $product)) ||
            !self::isConvertibleToInt(apply_filters('woocommerce_quantity_input_step', 1, $product));
    }

    private static function error($message)
    {
        trigger_error($message, E_USER_ERROR);
    }
}
