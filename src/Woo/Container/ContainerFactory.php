<?php

declare(strict_types=1);

namespace Twint\Woo\Container;

class ContainerFactory
{
    private static ?ContainerInterface $container = null;

    public static function instance(): ContainerInterface
    {
        return self::$container ??= new AliasingContainer(self::loadRegisteredServices());
    }

    private static function loadRegisteredServices(): array
    {
        require_once __DIR__ . '/../Di/services.php';

        return twint_services();
    }
}
