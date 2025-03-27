<?php
namespace WbsVendors\Dgm\Shengine\Woocommerce\Converters;

use Deferred\Deferred;
use Dgm\Shengine\Attributes\ProductVariationAttribute;
use Dgm\Shengine\Grouping\AttributeGrouping;
use Dgm\Shengine\Interfaces\IItem;
use Dgm\Shengine\Interfaces\IPackage;
use Dgm\Shengine\Model\Address;
use Dgm\Shengine\Model\Customer;
use Dgm\Shengine\Model\Destination;
use Dgm\Shengine\Model\Dimensions;
use Dgm\Shengine\Model\Package;
use Dgm\Shengine\Model\Price;
use Dgm\Shengine\Woocommerce\Model\Item\WoocommerceItem;
use Dgm\Shengine\Woocommerce\Model\Item\WpmlAwareItem;
use InvalidArgumentException;
use WC_Cart;
use WC_Product;
use WC_Product_Variation;


/** @noinspection PhpUnused */
class PackageConverter
{
    /**
     * @param IPackage $package
     * @return array
     * @noinspection PhpUnused
     */
    public static function fromCoreToWoocommerce(\WbsVendors\Dgm\Shengine\Interfaces\IPackage $package): array
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
     * @noinspection PhpUnused
     */
    public static function fromWoocommerceToCore(array $_package, WC_Cart $cart = null): \WbsVendors\Dgm\Shengine\Interfaces\IPackage
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
        array   $_package,
        WC_Cart $cart = null,
        bool    $preferCustomPackagePriceOverPerItemPrices = false,
        bool    $includeVirtualItemsIfPackageIsRoot = false
    ): \WbsVendors\Dgm\Shengine\Interfaces\IPackage {
        if (($preferCustomPackagePriceOverPerItemPrices || $includeVirtualItemsIfPackageIsRoot) && !isset($cart)) {
            throw new InvalidArgumentException('$cart is required for extended options, null given');
        }

        $skipNonShippableItems = true;
        if ($includeVirtualItemsIfPackageIsRoot && self::isGlobalPackage($_package, $cart)) {

            add_filter($fltr = 'woocommerce_product_needs_shipping', $fltrcb = '__return_true', $fltpr = PHP_INT_MAX, 0);
            $deferred = new \WbsVendors\Deferred\Deferred(function() use($fltr, $fltrcb, $fltpr) {
                remove_filter($fltr, $fltrcb, $fltpr);
            });

            $globalPackages = $cart->get_shipping_packages();

            unset($deferred);

            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
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
            $price = new \WbsVendors\Dgm\Shengine\Model\Price(
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
                $item = new \WbsVendors\Dgm\Shengine\Woocommerce\Model\Item\WpmlAwareItem(
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

            $postcode = $dest['postcode'] ?? null;
            if ($postcode !== null) {
                // WC calls wc_format_postcode on checkout, but the ajax checkout update does not.
                // This makes space-less UK postcodes work differently on ajax updates, e.g. '1AA11A' vs '1AA 11A'.
                $postcode = wc_format_postcode($postcode, $dest['country']);
            }

            $destination = new \WbsVendors\Dgm\Shengine\Model\Destination(
                $dest['country'],
                @$dest['state'],
                $postcode,
                @$dest['city'],
                new \WbsVendors\Dgm\Shengine\Model\Address(@$dest['address'], @$dest['address_2'])
            );
        }

        $customer = null;
        if (isset($_package['user']['ID'])) {
            $customer = new \WbsVendors\Dgm\Shengine\Model\Customer($_package['user']['ID']);
        }

        $coupons = array();
        if (!empty($_package['applied_coupons'])) {
            $coupons = array_map('strtolower', $_package['applied_coupons']);
        }

        $customPackagePrice = null;
        if ($preferCustomPackagePriceOverPerItemPrices && self::isGlobalPackage($_package, $cart)) {
            $customPackagePrice = new \WbsVendors\Dgm\Shengine\Model\Price(
                (float)$cart->get_subtotal(),
                (float)$cart->get_subtotal_tax(),
                (float)$cart->get_discount_total(),
                (float)$cart->get_discount_tax()
            );
        }

        return new \WbsVendors\Dgm\Shengine\Model\Package($items, $destination, $customer, $coupons, $customPackagePrice);
    }

    private static function isGlobalPackage($_package, WC_Cart $cart): bool
    {
        $globalPackages = $cart->get_shipping_packages();
        return count($globalPackages) === 1 && self::comparePackages(reset($globalPackages), $_package);
    }

    /** @noinspection IfReturnReturnSimplificationInspection */
    private static function comparePackages(array $package1, array $package2): bool
    {
        foreach (['rates', 'package_id', 'package_name'] as $key) {
            unset($package1[$key], $package2[$key]);
        }

        // fast path
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

    private static function makeWcItems(\WbsVendors\Dgm\Shengine\Interfaces\IPackage $package): array
    {
        $wcItems = array();

        $lineGrouping = new \WbsVendors\Dgm\Shengine\Grouping\AttributeGrouping(new \WbsVendors\Dgm\Shengine\Attributes\ProductVariationAttribute());
        $lines = $package->split($lineGrouping);

        foreach ($lines as $line) {

            $items = $line->getItems();
            if (!$items) {
                continue;
            }

            /** @var IItem $item */
            $item = reset($items);

            $product = null; {

                if ($item instanceof \WbsVendors\Dgm\Shengine\Woocommerce\Model\Item\WoocommerceItem) {
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

                $wcItem['line_total'] = $line->getPrice(\WbsVendors\Dgm\Shengine\Model\Price::WITH_DISCOUNT);
                $wcItem['line_tax'] = $line->getPrice(\WbsVendors\Dgm\Shengine\Model\Price::WITH_DISCOUNT | \WbsVendors\Dgm\Shengine\Model\Price::WITH_TAX) - $wcItem['line_total'];
                $wcItem['line_subtotal'] = $line->getPrice(\WbsVendors\Dgm\Shengine\Model\Price::BASE);
                $wcItem['line_subtotal_tax'] = $line->getPrice(\WbsVendors\Dgm\Shengine\Model\Price::WITH_TAX) - $wcItem['line_subtotal'];
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

    private static function getCustomerId(\WbsVendors\Dgm\Shengine\Interfaces\IPackage $package)
    {
        if ($customer = $package->getCustomer()) {
            return $customer->getId();
        }

        return null;
    }

    private static function getDestination(\WbsVendors\Dgm\Shengine\Interfaces\IPackage $package)
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
        return new \WbsVendors\Dgm\Shengine\Model\Dimensions(
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
                    return call_user_func(array((\WbsVendors_CCR::klass($product)), "get_{$attr}"));
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
        /** @noinspection TypeUnsafeComparisonInspection */
        return is_numeric($value) && (int)$value == (float)$value;
    }

    /**
     * @param WC_Product $product
     * @return bool
     */
    private static function supportsFractionalQuantity(WC_Product $product)
    {
        $fractional = static function($v) {
            return !($v === '' || $v === null || $v === false || self::isConvertibleToInt($v));
        };

        return
            $fractional(apply_filters('woocommerce_quantity_input_min', 0, $product)) ||
            $fractional(apply_filters('woocommerce_quantity_input_max', -1, $product)) ||
            $fractional(apply_filters('woocommerce_quantity_input_step', 1, $product));
    }

    private static function error($message)
    {
        trigger_error($message, E_USER_ERROR);
    }
}