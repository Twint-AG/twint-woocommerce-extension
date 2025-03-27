<?php

namespace RocketLazyLoadPlugin\Dependencies\LaunchpadOptions\Interfaces\Actions;

interface DeleteInterface
{
    /**
     * Deletes the option with the given name.
     *
     * @param string $name Name of the option to delete.
     *
     * @return void
     */
    public function delete( string $name );
}