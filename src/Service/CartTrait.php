<?php

declare(strict_types=1);

namespace Twint\Woo\Service;

use Automattic\WooCommerce\Blocks\StoreApi\Utilities\CartController;
use RuntimeException;

trait CartTrait
{
    /**
     * @var CartController|\Automattic\WooCommerce\StoreApi\Utilities\CartController|mixed
     */
    private mixed $controller;

    protected function getCartController(): void
    {
        $classes = [
            'Automattic\WooCommerce\StoreApi\Utilities\CartController',
            'Automattic\WooCommerce\Blocks\StoreApi\Utilities\CartController',
        ];

        foreach ($classes as $controller) {
            if (class_exists($controller)) {
                $this->controller = new $controller();
                return;
            }
        }

        // Handle the case where no controller class is found
        throw new RuntimeException('No valid CartController class found.');
    }
}
