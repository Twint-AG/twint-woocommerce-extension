<?php
namespace Gzp\WbsNg;

use Gzp\WbsNg\Mapping\Context;
use Gzp\WbsNg\Model\Config\Document;
use Gzp\WbsNg\Model\Config\Method;
use Gzp\WbsNg\Model\Order\Convert;
use GzpWbsNgVendors\Dgm\Shengine\Model\Rate as ShengineRate;
use GzpWbsNgVendors\Dgm\Shengine\Woocommerce\Converters\PackageConverter;
use GzpWbsNgVendors\Dgm\Shengine\Woocommerce\Converters\RateConverter;
use GzpWbsNgVendors\Dgm\WcTools\WcTools;
use WC_Shipping_Method;
use function Gzp\WbsNg\Common\map;


class ShippingMethod extends WC_Shipping_Method
{
    /**
     * @noinspection PhpMissingParentConstructorInspection
     * @noinspection MagicMethodsValidityInspection
     */
    public function __construct($instanceId = null)
    {
        $this->plugin_id = Plugin::ID;
        $this->id = Plugin::ID;
        $this->instance_id = absint($instanceId);

        $this->optionName = join('_', array_filter([
            $this->plugin_id,
            $this->instance_id,
            'config',
        ]));

        $this->supports = [
            'shipping-zones',
            'instance-settings',
            'global-instance',
        ];

        if (!$this->instance_id) {
            $globalMethodEnabled = !class_exists('Wbs\Plugin') || (get_option('wbs_global_methods') ?: 'only-wbsng') !== 'only-wbs';
            if ($globalMethodEnabled) {
                $this->supports[] = 'settings';
            }
            else {
                $this->enabled = 'no';
            }
        }

        $this->title = $this->method_title = 'Weight Based Shipping';

        if ($instanceId) {
            try {
                // Displaying the list of internal shipping methods via $this->title might be looking better
                // in the list of a zone's shipping methods, but it breaks the zones page since it escapes html.
                $this->method_description = join('', map($this->config()->methods, function(Method $m) {
                    return '<div '.($m->active() ? '' : 'style="color: #a7aaad"').' >'.esc_html($m->name).'</div>';
                }));
            }
            catch (ConfigError $e) {
            }
        }

        // When instance_id is empty, this text is shown to a user choosing a new shipping method from the list.
        // Otherwise, it's shown next to an existing shipping method instance.
        if (!$this->method_description) {
            $this->method_description = 'Lets you define flat, progressive, and free shipping options based on cart weight, price, and contents.';
        }
    }

    public function configData(): ?array
    {
        return get_option($this->optionName, null);
    }

    /**
     * @throws ConfigError
     */
    public function updateConfigData($data): void
    {
        $optionKey = $this->optionName;

        $this->config($data);

        $updated = update_option($optionKey, $data);
        if ($updated) {
            WcTools::purgeShippingCache();
        }
    }

    /**
     * @throws ConfigError
     * @noinspection PhpDocRedundantThrowsInspection
     */
    public function config(array $data = null): Document
    {
        $data = $data ?? $this->configData();
        if (!isset($data)) {
            return new Document();
        }

        $ctx = Context::of(
            $data,
            function(Context $ctx, \Throwable $e) {
                if ($e instanceof \Exception) {
                    $e = new ConfigError("config loading error at {$ctx->origin($e)->path()}: {$e->getMessage()}", 0, $e);
                }
                return $e;
            }
        );

        return $ctx->map([Document::class, 'unserialize']);
    }

    public function is_available($package): bool
    {
        // This fixes the issue with the global method not being triggered by WooCommerce for customers having no location set.
        // It also works fine for instanced shipping methods.
        return $this->is_enabled();
    }

    /**
     * @noinspection PhpParameterNameChangedDuringInheritanceInspection
     */
    public function calculate_shipping($_package = []): void
    {
        try {

            $config = $this->config();
            if (!$config->active()) {
                return;
            }

            [$items, $dest] = Convert::convert(PackageConverter::fromWoocommerceToCore2($_package, WC()->cart, true));

            $solutions = Shipping::solutions($items, $dest, $config);

        } catch (ConfigError $e) {
            wc_get_logger()->error($e->getMessage());
            return;
        }

        $rates = [];
        foreach ($solutions as $solution) {
            $rates[] = new ShengineRate($solution->price->__toString(), $solution->title);
        }

        $_rates = RateConverter::fromCoreToWoocommerce(
            $rates,
            $this->title,
            join(':', array_filter([$this->id, $this->instance_id ?? null])).':'
        );

        foreach ($_rates as $i => $_rate) {

            $solution = $solutions[$i];
            if ($solution->shipments->count() > 1) {
                $_rate['meta_data'][SolutionMeta::Key] = SolutionMeta::serialize($solution);
            }

            $this->add_rate($_rate);
        }
    }

    public function admin_options(): void
    {
        $client = new Client($this);
        WpTools::addActionOrCall('admin_enqueue_scripts', [$client, 'enqueueAssets'], PHP_INT_MAX);
        echo $client->html();
    }

    public function get_instance_id(): int
    {
        // A hack to prevent Woocommerce 2.6+ from skipping global method instance
        // rates in WC_Shipping::calculate_shipping_for_package()
        return $this->instance_id ?: -1;
    }

    public function get_option_key(): string
    {
        return '';
    }

    public function get_instance_option_key(): string
    {
        return '';
    }

    /**
     * @var string
     */
    private $optionName;
}