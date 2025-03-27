<?php declare(strict_types=1);

namespace Gzp\WbsNg;

use WC_Order_Item;
use WC_Order_Item_Shipping;
use WC_Shipping_Rate;


class Plugin
{
    public const ID = 'wbsng';

    /**
     * @readonly
     * @var PluginMeta
     */
    public $meta;

    public static function setupOnce(string $entrypoint): void
    {
        if (!isset(self::$instance)) {
            $plugin = new Plugin($entrypoint);
            $plugin->setup();
            self::$instance = $plugin;
        }
    }

    public static function instance(): self
    {
        return self::$instance;
    }

    public function setup(): void
    {
        Api::init();

        add_filter('woocommerce_shipping_methods', static function($shippingMethods) {

            $idx = array_search('wbs', array_keys($shippingMethods));
            if ($idx === false) {
                $idx = count($shippingMethods);
            }

            return
                array_slice($shippingMethods, 0, $idx, true) +
                [self::ID => ShippingMethod::class] +
                array_slice($shippingMethods, $idx, null, true);
        });

        // Show the solution breakdown on the frontend.
        add_action('woocommerce_after_shipping_rate', function(WC_Shipping_Rate $rate) {
            $this->renderSolution($rate->get_meta_data()[SolutionMeta::Key] ?? null);
        });

        // Show the solution breakdown on the backend order page.
        // At the moment, the breakdown is not editable and is not reset on shipping method change.
        add_action('woocommerce_before_order_itemmeta', function($_, WC_Order_Item $orderItem) {
            if ($orderItem instanceof WC_Order_Item_Shipping) {
                foreach ($orderItem->get_meta_data() as $meta) {
                    if ($meta->key === SolutionMeta::Key) {
                        $this->renderSolution($meta->value, false);
                    }
                }
            }
        }, 10, 2);

        add_filter('woocommerce_hidden_order_itemmeta', function(array $keys) {
            $keys[] = SolutionMeta::Key;
            return $keys;
        });
    }


    /** @var self */
    private static $instance;

    private function __construct(string $entrypoint)
    {
        $this->meta = new PluginMeta(wp_normalize_path($entrypoint));
    }

    private function renderSolution($data, $silent = true): void
    {
        $solution = SolutionMeta::unserialize($data, $error);
        if ($error && $silent) {
            wc_get_logger()->error($error);
            return;
        }

        $paths = $this->meta->paths;
        wp_enqueue_style('wbsng-solution-details-css', $paths->serverAssetUrl('solution-details.css'));
        include($paths->tpl('solution.tpl.php'));
    }
}
