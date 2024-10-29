<?php

declare(strict_types=1);

namespace Twint\Woo\Provider;

abstract class AbstractServiceProvider
{
    public static array $container = [];

    public static function getServiceByName(string $name)
    {
        return isset(self::$container[$name]) && !empty(self::$container[$name]) ? self::$container[$name] : null;
    }

    abstract public static function INSTANCE($args);

    public function registerToServiceContainer(string $name = '', $instance = null): void
    {
        if ($name !== '' && $name !== '0' && !empty($instance) && !array_key_exists($name, self::$container)) {
            self::$container[] = [
                $name => $instance,
            ];
        }
    }

    abstract protected function boot();

    abstract protected function load();
}
