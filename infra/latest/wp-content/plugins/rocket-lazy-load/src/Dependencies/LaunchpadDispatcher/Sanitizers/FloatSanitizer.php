<?php

namespace RocketLazyLoadPlugin\Dependencies\LaunchpadDispatcher\Sanitizers;

use RocketLazyLoadPlugin\Dependencies\LaunchpadDispatcher\Interfaces\SanitizerInterface;
use RocketLazyLoadPlugin\Dependencies\LaunchpadDispatcher\Traits\IsDefault;

class FloatSanitizer implements SanitizerInterface {
    use IsDefault;

    public function sanitize($value)
    {
        return (float) $value;
    }

}