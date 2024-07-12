<?php

namespace Twint\Woo\Includes\Admin\Settings\Tabs;

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twint\Woo\Abstract\Core\Setting\TabItem;

class RegularCheckout extends TabItem
{
    public static function getKey(): string
    {
        return '_' . str_replace('\\', '_', self::class);
    }

    public static function getLabel(): string
    {
        return __('Regular Checkout', 'woocommerce-gateway-twint');
    }

    public static function fields(): array
    {
        return [];
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public static function getContents(array $data = []): string
    {
        global $TWIG_TEMPLATE_ENGINE;
        $template = $TWIG_TEMPLATE_ENGINE;

        return $template
            ->load('Layouts/partials/tab-content-pages/regular_checkout.twig')
            ->render($data);
    }

    public static function allowSaveChanges(): bool
    {
        return false;
    }

}