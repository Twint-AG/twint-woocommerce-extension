<?php

namespace RocketLazyLoadPlugin\Dependencies\LaunchpadOptions\Interfaces;

use RocketLazyLoadPlugin\Dependencies\LaunchpadOptions\Interfaces\Actions\DeleteInterface;
use RocketLazyLoadPlugin\Dependencies\LaunchpadOptions\Interfaces\Actions\FetchInterface;
use RocketLazyLoadPlugin\Dependencies\LaunchpadOptions\Interfaces\Actions\SetInterface;

interface SettingsInterface extends DeleteInterface, FetchInterface, SetInterface {

    /**
     * Import multiple values at once.
     *
     * @param array<string,mixed> $values Values to import.
     *
     * @return void
     */
    public function import(array $values);

    /**
     * Export settings values.
     *
     * @return array<string,mixed>
     */
    public function dumps(): array;
}