<?php

declare(strict_types=1);

namespace Twint\Woo\Container;

use Twint\Woo\Di\ServiceDefinition;

class ContainerFactory
{
    private static ?ContainerInterface $container = null;

    public static function instance(): ContainerInterface
    {
        return self::$container ??= new AliasingContainer(self::loadRegisteredServices());
    }

    private static function loadRegisteredServices(): array
    {
        return ServiceDefinition::services();
    }
}
