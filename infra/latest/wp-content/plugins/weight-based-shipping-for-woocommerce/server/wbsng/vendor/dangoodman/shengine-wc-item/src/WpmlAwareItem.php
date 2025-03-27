<?php declare(strict_types=1);
namespace GzpWbsNgVendors\Dgm\Shengine\Woocommerce\Model\Item;


class WpmlAwareItem extends WoocommerceItem
{
    public function getTerms($taxonomy): array
    {
        $taxonomy = (string)$taxonomy;

        global $sitepress;
        if (!isset($sitepress)) {
            return parent::getTerms($taxonomy);
        }

        $legacy = (bool)apply_filters('trs_wpml_terms_legacy', false);

        $cacheKey = (int)$legacy.':'.$taxonomy;
        if (!isset($this->termsCache[$cacheKey])) {
            $this->termsCache[$cacheKey] = $this->doGetTerms($legacy, $taxonomy);
        }

        return $this->termsCache[$cacheKey];
    }

    private $termsCache = [];

    private function doGetTerms(bool $legacy, string $taxonomy): array
    {
        if ($legacy) {
            return $this->getTermsLegacy($taxonomy);
        }

        $terms = parent::getTerms($taxonomy);
        $terms = self::termsToDefaultLang($taxonomy, $terms);
        return $terms;
    }

    private function getTermsLegacy(string $taxonomy): array
    {
        global $sitepress;

        $lang = $sitepress->get_current_language();
        $sitepress->switch_lang($sitepress->get_default_language());
        try {
            return parent::getTerms($taxonomy);
        }
        finally {
            $sitepress->switch_lang($lang);
        }
    }

    private static function termsToDefaultLang(string $taxonomy, array $terms): array
    {
        $wptax = 'tax_'.self::mapTaxonomy($taxonomy);

        $defLangTerms = [];
        foreach ($terms as $termId) {

            $trid = apply_filters('wpml_element_trid', null, $termId, $wptax);
            if (!$trid) {
                continue;
            }

            $translations = apply_filters('wpml_get_element_translations', null, $trid, $wptax);
            if (!is_array($translations)) {
                continue;
            }

            foreach ($translations as $tr) {
                if ($tr->source_language_code === null) {
                    $termId = $tr->element_id;
                    break;
                }
            }

            $defLangTerms[] = $termId;
        }

        return $defLangTerms;
    }
}