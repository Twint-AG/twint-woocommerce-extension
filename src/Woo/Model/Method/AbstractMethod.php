<?php

declare(strict_types=1);

namespace Twint\Woo\Model\Method;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Twint\Plugin;
use Twint\Woo\Model\Gateway\AbstractGateway;
use Twint\Woo\Service\SettingService;

abstract class AbstractMethod extends AbstractPaymentMethodType
{
    /**
     * The gateway instance.
     */
    protected AbstractGateway $gateway;

    public function initialize(): void
    {
        $this->settings = get_option(SettingService::KEY_PRIMARY_SETTING, []);
        $gateways = WC()->payment_gateways();

        $validated = get_option(SettingService::FLAG_VALIDATED_CREDENTIAL_CONFIG);
        if (isset($gateways[$this->name]) && $validated === SettingService::YES) {
            $this->gateway = $gateways[$this->name];
        }
    }

    /**
     * Returns if this payment method should be active. If false, the scripts will not be enqueued.
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
        $scriptAssetPath = Plugin::abspath() . 'assets/js/frontend/blocks.asset.php';
        $scriptAsset = file_exists($scriptAssetPath)
            ? require ($scriptAssetPath)
            : [
                'dependencies' => [],
                'version' => '1.0.0',
            ];

        $scriptUrl = Plugin::dist('/checkout.js');

        wp_register_script(
            'wc-twint-payments-blocks',
            $scriptUrl,
            $scriptAsset['dependencies'],
            $scriptAsset['version'],
            true
        );

        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations(
                'wc-twint-payments-blocks',
                'woocommerce-gateway-twint',
                Plugin::abspath() . 'languages/'
            );
        }

        return ['wc-twint-payments-blocks'];
    }

    /**
     * Returns an array of key=>value pairs of data made available to the payment methods script.
     */
    public function get_payment_method_data(): array
    {
        return [
            'id' => $this->name,
            'title' => empty($this->get_setting('title')) ? 'TWINT' : $this->get_setting('title'),
            'description' => $this->get_setting('description'),
            'supports' => array_filter($this->gateway->supports, [$this->gateway, 'supports']),
        ];
    }
}
