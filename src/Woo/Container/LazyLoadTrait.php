<?php

declare(strict_types=1);

namespace Twint\Woo\Container;

use BadMethodCallException;

trait LazyLoadTrait
{
    public function __call(string $name, array $arguments)
    {
        $property = str_replace('get', '', $name);
        $property = lcfirst($property);
        //        dd($property);
        if (in_array($property, static::$lazyLoads, true)) {
            if ($this->{$property} instanceof Lazy) {
                //                dd($property);
                $this->{$property} = $this->{$property}->get();
            }

            return $this->{$property};
        }

        throw new BadMethodCallException($name);
    }
}
