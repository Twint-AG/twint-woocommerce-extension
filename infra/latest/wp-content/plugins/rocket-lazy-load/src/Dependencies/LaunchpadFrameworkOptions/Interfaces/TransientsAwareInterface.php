<?php

namespace RocketLazyLoadPlugin\Dependencies\LaunchpadFrameworkOptions\Interfaces;

use RocketLazyLoadPlugin\Dependencies\LaunchpadOptions\Interfaces\TransientsInterface;

interface TransientsAwareInterface
{
    /**
     * Set transients facade.
     *
     * @param TransientsInterface $transients Transients facade.
     * @return void
     */
    public function set_transients(TransientsInterface $transients);
}