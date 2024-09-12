<?php

namespace Twint\Woo\Helper;

class ArrayHelper
{
    /**
     * @param string $json
     * @param bool $associative
     * @return array|null
     */
    public static function toArray(string $json, bool $associative = false): array|null
    {
        if (empty($json)) {
            return [];
        }

        return json_decode($json, $associative);
    }
}
