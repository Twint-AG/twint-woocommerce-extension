<?php

declare(strict_types=1);

// Isolated worker tests. No WordPress database or payment API is used.
require dirname(__DIR__) . '/vendor/autoload.php';

// The real NullLogger only needs this type during these tests.
interface WC_Logger_Interface
{
}

function __($text, $domain = '')
{
    return $text;
}
