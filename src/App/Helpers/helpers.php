<?php

// Helper function goes here

if (!function_exists('ddd')) {
    function ddd(...$args): void
    {
        \TWINT\Utility\Dump::die(...$args);
    }
}