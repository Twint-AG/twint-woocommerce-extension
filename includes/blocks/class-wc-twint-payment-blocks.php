<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Twint\Woo\Services\SettingService;

/**
 * Twint Payment Blocks integration
 *
 * @since 1.0.0
 */
final class WC_Gateway_Twint_Regular_Checkout_Blocks_Support extends AbstractPaymentMethodType
{

    /**
     * The gateway instance.
     *
     * @var WC_Gateway_Twint_Regular_Checkout
     */
    private WC_Gateway_Twint_Regular_Checkout $gateway;

    /**
     * Payment method name/id/slug.
     *
     * @var string
     */
    protected $name = 'twint_regular';

    /**
     * Initializes the payment method type.
     */
    public function initialize(): void
    {
        $this->settings = get_option(SettingService::KEY_PRIMARY_SETTING, []);
        $gateways = WC()->payment_gateways()->payment_gateways();

        $flagValidatedCredentials = get_option(SettingService::FLAG_VALIDATED_CREDENTIAL_CONFIG);
        if (isset($gateways[$this->name]) && SettingService::YES === $flagValidatedCredentials) {
            $this->gateway = $gateways[$this->name];
        }
    }

    /**
     * Returns if this payment method should be active. If false, the scripts will not be enqueued.
     *
     * @return boolean
     */
    public function is_active(): bool
    {
        if (!empty($this->gateway)) {
            return $this->gateway->is_available();
        }

        return false;
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
                'version' => '1.0.0'
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
            'title' => !empty($this->get_setting('title')) ? $this->get_setting('title') : 'TWINT',
            'description' => $this->get_setting('description'),
            'supports' => array_filter($this->gateway->supports, [$this->gateway, 'supports'])
        ];
    }
}
