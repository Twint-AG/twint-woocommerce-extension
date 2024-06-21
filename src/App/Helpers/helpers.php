<?php

// Helper function goes here

if (!function_exists('ddd')) {
    function ddd(...$args): void
    {
        \TWINT\Utility\Dump::die(...$args);
    }
}


if (!function_exists('isValidUuid')) {
    /**
     * Check if a given string is a valid UUID
     *
     * @param string $uuid The string to check
     * @return  boolean
     */
    function isValidUuid(string $uuid): bool
    {
        if ((preg_match('/^[a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{8}$/i', $uuid) !== 1)) {
            return false;
        }

        return true;
    }
}