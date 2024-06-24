<?php

namespace TWINT\Abstract\ServiceProvider;

abstract class AbstractServiceProvider
{
    public static array $container = [];

    public function __construct($args = [])
    {
    }

    public function registerToServiceContainer(string $name = '', $instance = null): void
    {
        if (!empty($name) && !empty($instance) && !array_key_exists($name,self::$container)) {
            self::$container[] = [$name => $instance];
        }
    }

    public static function getServiceByName(string $name)
    {
        return isset(self::$container[$name]) && !empty(self::$container[$name]) ? self::$container[$name] : null;
    }

    abstract protected function boot();

    abstract protected function load();

    abstract public static function INSTANCE($args);
}