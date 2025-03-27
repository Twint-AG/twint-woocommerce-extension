<?php

namespace RocketLazyLoadPlugin\Dependencies\LaunchpadDispatcher\Sanitizers;

use RocketLazyLoadPlugin\Dependencies\LaunchpadDispatcher\Interfaces\SanitizerInterface;
use RocketLazyLoadPlugin\Dependencies\LaunchpadDispatcher\Traits\IsDefault;

class BoolSanitizer implements SanitizerInterface {

    use IsDefault;

    public function sanitize($value)
    {
        return (bool) $value;
    }
}