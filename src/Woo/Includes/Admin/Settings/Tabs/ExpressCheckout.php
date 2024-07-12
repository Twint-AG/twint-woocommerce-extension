<?php

namespace Twint\Woo\Includes\Admin\Settings\Tabs;

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twint\Woo\Abstract\Core\Setting\TabItem;

class ExpressCheckout extends TabItem
{
    public static function getKey(): string
    {
        return '_' . str_replace('\\', '_', self::class);
    }

    public static function getLabel(): string
    {
        return __('Express Checkout', 'woocommerce-gateway-twint');
    }

    public static function fields(): array
    {
        return [];
    }

    public static function directLink(): string
    {
        $params = [
            'page' => 'wc-settings',
            'tab' => 'checkout',
            'section' => 'twint_express',
        ];

        return add_query_arg($params, admin_url('admin.php'));
    }

    public static function getContents(array $data = []): string
    {
        return '';
    }

    public static function allowSaveChanges(): bool
    {
        return false;
    }
}