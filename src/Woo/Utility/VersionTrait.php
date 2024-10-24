<?php

declare(strict_types=1);

namespace Twint\Woo\Utility;

trait VersionTrait
{
    private function getSystemVersions(): string
    {
        global $wp_version;
        $wooVersion = WC()->version;

        return "{$wp_version}-{$wooVersion}";
    }
}
