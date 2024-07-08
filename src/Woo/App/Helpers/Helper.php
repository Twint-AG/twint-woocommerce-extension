<?php

namespace Twint\Woo\App\Helpers;

class Helper
{
    /**
     * @param $jsonAsString
     * @param bool $associative
     * @return array|null
     */
    public static function jsonAsStringToArray($jsonAsString, bool $associative = false): array|null
    {
        if (empty($jsonAsString)) {
            return [];
        }

        if (is_array($jsonAsString)) {
            return $jsonAsString;
        }

        if (!is_string($jsonAsString)) {
            return [];
        }

        return json_decode($jsonAsString, $associative);
    }
}