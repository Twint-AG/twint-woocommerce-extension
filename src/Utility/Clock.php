<?php

declare(strict_types=1);

namespace Twint\Woo\Utility;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;

class Clock implements ClockInterface
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now');
    }
}
