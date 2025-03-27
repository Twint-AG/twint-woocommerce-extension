<?php

namespace RocketLazyLoadPlugin\Dependencies\LaunchpadOptions\Interfaces\Actions;

interface FetchInterface
{

    /**
     * Gets the option for the given name. Returns the default value if the value does not exist.
     *
     * @param string $name   Name of the option to get.
     * @param mixed  $default Default value to return if the value does not exist.
     *
     * @return mixed
     */
    public function get( string $name, $default = null );

    /**
     * Checks if the option with the given name exists.
     *
     * @param string $name Name of the option to check.
     *
     * @return bool
     */
    public function has( string $name ): bool;
}