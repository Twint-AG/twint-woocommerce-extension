<?php

declare(strict_types=1);

namespace Twint\Woo\Helper;

class ArrayHelper
{
    public static function toArray(string $json, bool $associative = false): array|null
    {
        if ($json === '' || $json === '0') {
            return [];
        }

        return json_decode($json, $associative);
    }
}
