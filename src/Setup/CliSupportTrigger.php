<?php

declare(strict_types=1);

namespace Twint\Woo\Setup;

use Twint\Woo\Service\BackgroundMonitor;

class CliSupportTrigger
{
    public function handle(): void
    {
        BackgroundMonitor::requestHealthCheck();
    }
}
