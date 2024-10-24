<?php

declare(strict_types=1);

namespace Twint\Woo\Helper;

class StringHelper
{
    /**
     * Check if a given string is a valid UUID
     *
     * @param string $uuid The string to check
     */
    public static function isValidUuid(string $uuid): bool
    {
        return preg_match('/^[a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{8}$/i', $uuid) === 1;
    }
}
