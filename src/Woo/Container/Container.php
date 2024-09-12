<?php

namespace Twint\Woo\Container;

class Container
{
    static private ?ContainerInterface $container = null;

    public static function instance(): ContainerInterface
    {
        if (!self::$container) {
            self::$container = new AliasingContainer(self::loadRegisteredServices());
        }

        return self::$container;
    }

    private static function loadRegisteredServices(): array
    {
        require_once __DIR__ . '/../Di/services.php';

        return twint_services();
    }
}
