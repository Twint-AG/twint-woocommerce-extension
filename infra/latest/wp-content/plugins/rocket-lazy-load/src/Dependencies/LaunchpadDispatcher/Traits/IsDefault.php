<?php

namespace RocketLazyLoadPlugin\Dependencies\LaunchpadDispatcher\Traits;

trait IsDefault
{
    public function is_default($value, $original): bool
    {
        return $value !== $original;
    }
}