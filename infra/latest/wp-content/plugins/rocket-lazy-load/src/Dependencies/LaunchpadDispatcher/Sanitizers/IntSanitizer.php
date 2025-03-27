<?php

namespace RocketLazyLoadPlugin\Dependencies\LaunchpadDispatcher\Sanitizers;

use RocketLazyLoadPlugin\Dependencies\LaunchpadDispatcher\Interfaces\SanitizerInterface;
use RocketLazyLoadPlugin\Dependencies\LaunchpadDispatcher\Traits\IsDefault;

class IntSanitizer implements SanitizerInterface {
    use IsDefault;

    public function sanitize($value)
    {
        return (int) $value;
    }
}