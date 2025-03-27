<?php

namespace RocketLazyLoadPlugin\Dependencies\LaunchpadFrameworkOptions\Interfaces;

use RocketLazyLoadPlugin\Dependencies\LaunchpadOptions\Interfaces\SettingsInterface;

interface SettingsAwareInterface
{
    /**
     * Set settings facade.
     *
     * @param SettingsInterface $settings Settings facade.
     * @return void
     */
    public function set_settings(SettingsInterface $settings);
}