<?php
namespace GzpWbsNgVendors\Dgm\Shengine\Woocommerce\Model\Item;

use GzpWbsNgVendors\Dgm\Shengine\Model\Item;
use RuntimeException;
use GzpWbsNgVendors\Dgm\Arrays\Arrays;
use WC_Product;


class WoocommerceItem extends Item
{
    public function getTerms($taxonomy): array
    {
        $taxonomy = self::mapTaxonomy((string)$taxonomy);

        $productId = $this->getProductId();
        $variationId = $this->getProductVariationId();

        $termsResult = false;

        // Find a term if the taxonomy is related to some product attribute used in this product variation.
        // That requires a special handling since Woocommerce stores variation attributes in a special way.
        if ($termsResult === false && isset($variationId) &&
            in_array($taxonomy, wc_get_attribute_taxonomy_names(), true) &&
            ($attributeTermSlug = @$this->variationAttributes[$taxonomy]) !== null) {

            $termsResult = array();

            foreach (get_the_terms($productId, $taxonomy) as $term) {

                if ($term->slug === $attributeTermSlug) {
                    $termsResult[] = $term;
                    break;
                }
            }
        }

        if ($termsResult === false && isset($variationId)) {
            $termsResult = get_the_terms($variationId, $taxonomy);
        }

        if ($termsResult === false) {
            $termsResult = get_the_terms($productId, $taxonomy);
        }

        if ($termsResult === false) {
            $termsResult = array();
        }

        if (is_wp_error($termsResult)) {
            throw new RuntimeException($termsResult->get_error_message());
        }

        return Arrays::map($termsResult, function ($term) {
            return $term->term_id;
        });
    }

    public function setTerms($taxonomy, array $terms = null)
    {
        throw new \BadMethodCallException("Setting terms on woocommerce item is not supported");
    }

    public function getOriginalProductObject()
    {
        return $this->originalProductObject;
    }

    public function setOriginalProductObject(WC_Product $product)
    {
        $this->originalProductObject = $product;
    }

    public function setVariationAttributes(array $attributes)
    {
        $this->variationAttributes = $attributes;
    }

    protected static function mapTaxonomy(string $taxonomy): string
    {
        if (isset(self::$map[$taxonomy])) {
            $taxonomy = self::$map[$taxonomy];
        }

        return $taxonomy;
    }

    /** @var WC_Product */
    private $originalProductObject;
    private $variationAttributes;

    private static $map = array(
        self::TAXONOMY_SHIPPING_CLASS => 'product_shipping_class',
        self::TAXONOMY_TAG => 'product_tag',
        self::TAXONOMY_CATEGORY => 'product_cat',
    );
}