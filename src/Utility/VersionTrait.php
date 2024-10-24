<?php

declare(strict_types=1);

namespace Twint\Woo\Utility;

trait VersionTrait
{
    private function getSystemVersions(): array
    {
        global $wp_version;
        $wooVersion = WC()->version;

        return [$wooVersion, $wp_version];
    }
}
