<?php

use Dgm\ComposerCapsule\Runtime\Runtime;


/**
 * @method static bool spl_autoload_register($autoloader, $throw, $prepend)
 * @method static bool spl_autoload_unregister($autoloader)
 * @method static callable kallable($callable)
 * @method static mixed klass($class)
 */
class WbsVendors_CCR
{
    static function __callStatic($name, $arguments)
    {
        return call_user_func_array(array(self::$instance, $name), $arguments);
    }

    /** @var Runtime */
    static $instance;
}