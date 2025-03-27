<?php

namespace RocketLazyLoadPlugin\Dependencies\LaunchpadOptions\Interfaces\Actions;

interface FetchPrefixInterface
{
    /**
     * Gets the transient name used to store the transient in the WordPress database.
     *
     * @param string $name Unprefixed name of the transient.
     *
     * @return string
     */
    public function get_full_key( string $name): string;
}