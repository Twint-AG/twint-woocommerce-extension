<?php

namespace RocketLazyLoadPlugin\Dependencies\LaunchpadOptions\Interfaces\Actions;

interface SetInterface
{

    /**
     * Sets the value of an option. Update the value if the option for the given name already exists.
     *
     * @param string $name Name of the option to set.
     * @param mixed  $value Value to set for the option.
     *
     * @return void
     */
    public function set( string $name, $value );
}