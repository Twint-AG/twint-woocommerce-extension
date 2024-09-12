<?php

namespace Twint\Woo\Utility;

class Str
{
    public const UTF8 = 'UTF-8';
    public const ASCII = 'ASCII';
    public const LATIN1 = 'ISO-8859-1';

    /**
     * HTML class names helper
     *
     * @param string $defaults
     * @param null|array $classes
     * @param string $failed
     * @return string
     */
    public static function classNames($defaults, array $classes = null, string $failed = ''): string
    {
        if (!$result = Arr::reduceAllowedStr(is_array($defaults) ? $defaults : $classes)) {
            $result = !is_array($classes) ? $classes : $failed;
        }

        $defaults = !is_array($defaults) ? $defaults : '';

        return $defaults . ' ' . $result;
    }

    /**
     * Blank
     *
     * Blank value or empty string
     *
     * @param string|null $string
     * @return bool
     */
    public static function blank(?string $string): bool
    {
        return !isset($string) || $string === '';
    }

    /**
     * Not Blank
     *
     * Not blank value or empty string
     *
     * @param string|null $value
     *
     * @return bool
     */
    public static function notBlank(?string $value): bool
    {
        return !(!isset($value) || $value === '');
    }

    /**
     * String Ends
     *
     * @param string $needle
     * @param string $haystack
     *
     * @return bool
     */
    public static function ends(string $needle, string $haystack): bool
    {
        return str_ends_with($haystack, $needle);
    }

    /**
     * String Contains
     *
     * @param string $needle
     * @param string $haystack
     *
     * @return bool
     */
    public static function contains(string $needle, string $haystack): bool
    {
        return str_contains($haystack, $needle);
    }

    /**
     * String Starts
     *
     * @param string $needle
     * @param string $haystack
     *
     * @return bool
     */
    public static function starts(string $needle, string $haystack): bool
    {
        return str_starts_with($haystack, $needle);
    }

    /**
     * Convert To Camel Case
     *
     * @param string $input
     * @param string $separator specify - or _
     * @param bool $capitalize_first_char define as false if you want camelCase over CamelCase
     *
     * @return array|string|string[]
     */
    public static function camelize(string $input, string $separator = '_', bool $capitalize_first_char = true)
    {
        $str = str_replace($separator, '', ucwords($input, $separator));

        if (!$capitalize_first_char) {
            $str = lcfirst($str);
        }

        return $str;
    }

    /**
     * Snake Case
     *
     * @param $input
     *
     * @return string
     */
    public static function snake($input): string
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }

    /**
     * Trim Start
     *
     * @param string $subject
     * @param string $trim
     * @return string
     */
    public static function trimStart(string $subject, string $trim = '/'): string
    {
        if (substr($subject, 0, strlen($trim)) == $trim) {
            $subject = substr($subject, strlen($trim));
        }

        return $subject;
    }

    /**
     * Replace First
     *
     * @param string $pattern
     * @param string $new
     * @param string $subject
     * @param bool $escape
     * @return string|string[]|null
     */
    public static function replaceFirstRegex(string $pattern, string $new, string $subject, bool $escape = true)
    {
        $pattern = $escape ? '/' . preg_quote($pattern, '/') . '/' : $pattern;
        return preg_replace($pattern, $new, $subject, 1);
    }

    /**
     * Replace the first occurrence of a given value in the string.
     *
     * @param string $search
     * @param string $new
     * @param string $subject
     * @return string
     */
    public static function replaceFirst(string $search, string $new, string $subject): string
    {
        if ($search == '') {
            return $subject;
        }

        $position = strpos($subject, $search);

        if ($position !== false) {
            return substr_replace($subject, $new, $position, strlen($search));
        }

        return $subject;
    }

    /**
     * Replace the last occurrence of a given value in the string.
     *
     * @param string $search
     * @param string $new
     * @param string $subject
     * @return string
     */
    public static function replaceLast(string $search, string $new, string $subject): string
    {
        $position = strrpos($subject, $search);

        if ($position !== false) {
            return substr_replace($subject, $new, $position, strlen($search));
        }

        return $subject;
    }

    /**
     * @param array|object $patterns
     * @param string $subject
     *
     * @return false|mixed
     */
    public static function pregMatchFindFirst(array $patterns, string $subject)
    {
        $regex = ['#^(?'];
        foreach ($patterns as $i => $pattern) {
            if ($reg = is_string($pattern) ? $pattern : Data::walk(['regex'], $pattern)) {
                $regex[] = $reg . '(*MARK:' . $i . ')';
            }
        }
        $regex = implode('|', $regex) . ')$#x';
        preg_match($regex, $subject, $m);

        if (empty($m)) {
            return null;
        }

        $found = isset($m['MARK']) && is_numeric($m['MARK']) ? $patterns[$m['MARK']] : null;
        if (empty($found)) {
            return null;
        }

        return $found;
    }

    /**
     * Split At
     *
     * @param $pattern
     * @param $subject
     * @param bool $last
     * @return array
     */
    public static function splitAt($pattern, $subject, bool $last = false): array
    {
        if (!$last) {
            return array_pad(explode($pattern, $subject, 2), 2, null);
        }

        $parts = explode($pattern, $subject);
        $last = array_pop($parts);
        $first = implode($pattern, $parts);
        return [$first ?: null, $last];
    }

    /**
     * Explode Starting From Right
     *
     * @param string $separator
     * @param string $string
     * @param int $limit
     *
     * @return array
     */
    public static function explodeFromRight(string $separator, string $string, int $limit = PHP_INT_MAX): array
    {
        return array_reverse(array_map('strrev', explode($separator, strrev($string), $limit)));
    }

    /**
     * Make Words
     *
     * @param string $subject
     * @param bool $uppercase
     * @param string $separator
     * @return array|false|string|string[]
     */
    public static function makeWords(string $subject, bool $uppercase, string $separator = '_')
    {
        $words = str_replace($separator, ' ', $subject);
        return $uppercase ? static::uppercaseWords($words) : $words;
    }

    /**
     * @param string $str
     *
     * @return false|string
     */
    public static function uppercaseWords(string $str)
    {
        return mb_convert_case($str, MB_CASE_TITLE, static::UTF8);
    }

    /**
     * Limit Length
     *
     * @param string $string
     * @param int $limit
     * @param string $end
     *
     * @return string
     */
    public static function limit(string $string, int $limit, string $end = ''): string
    {
        $length = static::length($string, static::UTF8);

        if ($length <= $limit) {
            return $string;
        }

        $width = mb_strwidth($string, static::UTF8) - $length;

        return Str . phprtrim(mb_strimwidth($string, 0, $limit + $width, '', static::UTF8)) . $end;
    }

    /**
     * Length
     *
     * @param string $string
     * @param string|null $encoding
     *
     * @return int
     */
    public static function length($string, ?string $encoding = null): int
    {
        if ($encoding) {
            return mb_strlen($string, $encoding);
        }

        return mb_strlen($string);
    }

    /**
     * Maxed
     *
     * Is string max length
     *
     * @param string $string
     * @param int $max
     * @param string|null $encoding
     *
     * @return bool
     */
    public static function maxed(string $string, int $max, ?string $encoding = null): bool
    {
        return !(static::length($string, $encoding) <= $max);
    }

    /**
     * Min
     *
     * Is string min length
     *
     * @param string $string
     * @param int $min
     * @param string|null $encoding
     *
     * @return bool
     */
    public static function min(string $string, int $min, ?string $encoding = null): bool
    {
        return static::length($string, $encoding) >= $min;
    }

    /**
     * Convert the given string to lower-case.
     *
     * @param string $string
     * @param string|null $encoding
     *
     * @return string
     */
    public static function lower(string $string, ?string $encoding = null): string
    {
        return mb_strtolower($string, $encoding ?? static::UTF8);
    }

    /**
     * Get Encoding
     *
     * @param string|null $encoding
     *
     * @return string
     */
    public static function encoding(?string $encoding = null): string
    {
        $encoding = $encoding ?? mb_internal_encoding();
        return !static::quiet($encoding) ? $encoding : static::UTF8;
    }

    /**
     * Quiet
     *
     * Is null or is blank after trim.
     *
     * @param string|null $string
     *
     * @return bool
     */
    public static function quiet(?string $string): bool
    {
        return !isset($string) || (trim($string) === '');
    }

    /**
     * Reverse
     *
     * @param string $string
     *
     * @return string
     */
    public static function reverse(string $string): string
    {
        return implode(array_reverse(mb_str_split($string)));
    }

}
