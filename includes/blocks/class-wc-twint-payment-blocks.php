<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use TWINT\Woo\Services\SettingService;

/**
 * Twint Payment Blocks integration
 *
 * @since 0.0.1
 */
final class WC_Gateway_Twint_Blocks_Support extends AbstractPaymentMethodType
{

    /**
     * The gateway instance.
     *
     * @var WC_Gateway_Twint
     */
    private WC_Gateway_Twint $gateway;

    /**
     * Payment method name/id/slug.
     *
     * @var string
     */
    protected $name = 'twint';

    /**
     * Initializes the payment method type.
     */
    public function initialize(): void
    {
        $this->settings = get_option(SettingService::KEY_PRIMARY_SETTING, []);
        $gateways = WC()->payment_gateways()->payment_gateways();
        $this->gateway = $gateways[$this->name];
    }

    /**
     * Returns if this payment method should be active. If false, the scripts will not be enqueued.
     *
     * @return boolean
     */
    public function is_active(): bool
    {
        return $this->gateway->is_available();
    }

    /**
     * Returns an array of scripts/handles to be registered for this payment method.
     *
     * @return array
     */
    public function get_payment_method_script_handles(): array
    {
        $script_path = '/assets/js/frontend/blocks.js';
        $script_asset_path = WC_Twint_Payments::plugin_abspath() . 'assets/js/frontend/blocks.asset.php';
        $script_asset = file_exists($script_asset_path)
            ? require($script_asset_path)
            : array(
                'dependencies' => [],
                'version' => '0.0.1'
            );
        $script_url = WC_Twint_Payments::plugin_url() . $script_path;

        wp_register_script(
            'wc-twint-payments-blocks',
            $script_url,
            $script_asset['dependencies'],
            $script_asset['version'],
            true
        );

        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('wc-twint-payments-blocks', 'woocommerce-gateway-twint', WC_Twint_Payments::plugin_abspath() . 'languages/');
        }

        return ['wc-twint-payments-blocks'];
    }

    /**
     * Returns an array of key=>value pairs of data made available to the payment methods script.
     *
     * @return array
     */
    public function get_payment_method_data(): array
    {
        return [
            'title' => $this->get_setting('title'),
            'description' => $this->get_setting('description'),
            'supports' => array_filter($this->gateway->supports, [$this->gateway, 'supports'])
        ];
    }
}
