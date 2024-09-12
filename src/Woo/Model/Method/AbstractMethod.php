<?php

namespace Twint\Woo\Model\Method;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Twint\TwintPayment;
use Twint\Woo\Model\Gateway\AbstractGateway;
use Twint\Woo\Service\SettingService;

abstract class AbstractMethod extends AbstractPaymentMethodType
{
    /**
     * The gateway instance.
     *
     * @var AbstractGateway
     */

    protected AbstractGateway $gateway;

    public function initialize(): void
    {
        $this->settings = get_option(SettingService::KEY_PRIMARY_SETTING, []);
        $gateways = WC()->payment_gateways()->payment_gateways();

        $validated = get_option(SettingService::FLAG_VALIDATED_CREDENTIAL_CONFIG);
        if (isset($gateways[$this->name]) && SettingService::YES === $validated) {
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

    public function get_payment_method_script_handles(): array
    {
        $scriptPath = '/dist/frontend/blocks.js';
        $scriptAssetPath = TwintPayment::abspath() . 'assets/js/frontend/blocks.asset.php';
        $scriptAsset = file_exists($scriptAssetPath)
            ? require($scriptAssetPath)
            : [
                'dependencies' => [],
                'version' => '1.0.0'
            ];

        $scriptUrl = TwintPayment::pluginUrl() . $scriptPath;

        wp_register_script(
            'wc-twint-payments-blocks',
            $scriptUrl,
            $scriptAsset['dependencies'],
            $scriptAsset['version'],
            true
        );

        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('wc-twint-payments-blocks', 'woocommerce-gateway-twint', TwintPayment::abspath() . 'languages/');
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
            'id' => $this->name,
            'title' => !empty($this->get_setting('title')) ? $this->get_setting('title') : 'TWINT',
            'description' => $this->get_setting('description'),
            'supports' => array_filter($this->gateway->supports, [$this->gateway, 'supports'])
        ];
    }
}
