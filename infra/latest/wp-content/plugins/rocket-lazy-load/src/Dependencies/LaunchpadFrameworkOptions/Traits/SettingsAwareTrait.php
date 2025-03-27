<?php

namespace RocketLazyLoadPlugin\Dependencies\LaunchpadFrameworkOptions\Traits;

use RocketLazyLoadPlugin\Dependencies\LaunchpadOptions\Interfaces\SettingsInterface;

trait SettingsAwareTrait
{
    /**
     * Set settings facade.
     *
     * @var SettingsInterface
     */
    protected $settings;

    /**
     * Set settings facade.
     *
     * @param SettingsInterface $settings Settings facade.
     * @return void
     */
    public function set_settings(SettingsInterface $settings)
    {
        $this->settings = $settings;
    }
}