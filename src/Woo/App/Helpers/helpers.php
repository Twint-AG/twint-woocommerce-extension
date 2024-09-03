<?php

// Helper function goes here

if (!function_exists('xmlBeautiful')) {
    function xmlBeautiful($xml): bool|string
    {
        $dom = new \DOMDocument('1.0');
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = true;
        $dom->loadXML($xml);
        return $dom->saveXML();
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

if (!function_exists('twint_assets')) {
    function twint_assets(string $asset = null): ?string
    {
        $localPath = WC_Twint_Payments::plugin_url() . '/assets';
        if (empty($asset)) {
            return '';
        }

        return $localPath . $asset;
    }
}