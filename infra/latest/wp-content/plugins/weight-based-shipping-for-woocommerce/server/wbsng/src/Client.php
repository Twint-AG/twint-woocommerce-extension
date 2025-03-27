<?php declare(strict_types=1);

namespace Gzp\WbsNg;

use Gzp\WbsNg\Model\Order\Item;
use GzpWbsNgVendors\Dgm\Arrays\Arrays;
use WP_Term;


class Client
{
    public function __construct(ShippingMethod $method)
    {
        $this->method = $method;
    }

    public function html(): string
    {
        $this->init();

        $html = '';

        {
            $hide = "#mainform p.submit";
            if (!$this->method->instance_id) { // global instance
                $hide .= ",#mainform>h2";
            }
            $html .= "<style>
                $hide {display:none}
                .woocommerce-recommended-shipping-extensions {
                    display: none !important;
                }
            </style>";
        }

        $styles = Arrays::map($this->css, static function($css) {
            $css = htmlspecialchars($css);
            return '<link rel="stylesheet" href="'.$css.'">';
        });
        $styles = join('', $styles);

        $html .= '<div id="gzp_wbsng_root">'.$styles.'</div>';
        $html .= '<script>document.getElementById("gzp_wbsng_root").attachShadow({mode: "open"})</script>';


        return $html;
    }

    public function enqueueAssets(): void
    {
        $this->init();

        // @font-face doesn't work inside shadow dom in Chrome, so define it globally
        wp_enqueue_style('gzp-wbsng-roboto', 'https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=block');

        if (!$this->js) {
            return;
        }

        $jsid = null;
        foreach ($this->js as $js) {
            $id = 'gzp-wbsng-'.$js;
            if (!isset($jsid)) {
                $jsid = $id;
            }
            wp_enqueue_script($id, $js, [], null);
        }

        $currencyPlacement = explode('_', get_option('woocommerce_currency_pos'));

        $globalMethods = null;
        if (!$this->method->instance_id && class_exists('Wbs\Api') && class_exists('Wbs\Plugin')) {
            $globalMethods = [
                'state' => get_option('wbs_global_methods') ?: 'only-wbsng',
                'endpoint' => \Wbs\Api::$globalSwitch->url(),
                'wbsRedirectUrl' => \Wbs\Plugin::shippingUrl(\Wbs\Plugin::ID),
            ];
        }

        $config = $this->method->configData();

        wp_localize_script($jsid, 'gzp_wbsng_js_data', [

            'config' => $config,

            'endpoints' => [
                'config' => Api::configEndpointUrl($this->method->instance_id),
            ],

            'nextUrlHeader' => Api::NextUrlHeader,

            'units' => [
                'weight' => [
                    'symbol' => get_option('woocommerce_weight_unit'),
                ],
                'price' => [
                    'symbol' => html_entity_decode(get_woocommerce_currency_symbol()),
                    'right' => $currencyPlacement[0] === 'right',
                    'withSpace' => isset($currencyPlacement[1]) && $currencyPlacement[1] === 'space',
                    'decimals' => wc_get_price_decimals(),
                ],
                'volume' => [
                    'symbol' => get_option('woocommerce_dimension_unit').'³',
                ],
            ],

            'dicts' => [
                'classes' => self::getAllShippingClasses(),
                'locations' => self::getAllLocations(),
            ],

            'globalMethods' => $globalMethods,

            'goToShippingZones' => $this->showGlobalMethodStub()
                ? admin_url('admin.php?page=wc-settings&tab=shipping')
                : null,
        ]);
    }

    private function init(): void
    {
        if ($this->inited) {
            return;
        }

        $paths = Plugin::instance()->meta->paths;

        $this->js[] = $paths->serverAssetUrl('client.js');
        $this->css[] = $paths->serverAssetUrl('client.css');

        $this->inited = true;
    }

    private function showGlobalMethodStub(): bool
    {
        if ($this->method->instance_id || array_key_exists('wbs_global', $_GET)) return false;

        try { $doc = $this->method->config(); }
        catch (\Throwable $e) { return false; }

        $methods = $doc->methods;
        if (count($methods) > 1) return false;
        if (count($methods) === 1 && !reset($methods)->empty()) return false;

        return true;
    }

    private static function getAllLocations(): array
    {
        $locations = [];

        foreach (WC()->countries->get_shipping_countries() as $cc => $countryName) {

            $country = [
                'id' => (string)$cc,
                'label' => html_entity_decode($countryName),
            ];

            if ($states = WC()->countries->get_states($cc)) {
                foreach ($states as $sc => $state) {
                    $country['nodes'][] = [
                        'id' => $sc,
                        'label' => html_entity_decode($state),
                    ];
                }
            }

            $locations[] = $country;
        }

        return $locations;
    }

    private static function getAllShippingClasses(): array
    {
        $shclasses = [];

        $shclasses[] = [
            'id' => Item::NONE_VIRTUAL_TERM_ID,
            'label' => __('No shipping class', 'woocommerce'),
        ];

        /** @var WP_Term $term */
        foreach (WC()->shipping()->get_shipping_classes() as $term) {
            $shclasses[] = [
                'id' => (int)$term->term_id,
                'label' => (string)$term->name,
            ];
        }

        return $shclasses;
    }

    private $inited = false;
    private $css;
    private $js;
    private $method;
}