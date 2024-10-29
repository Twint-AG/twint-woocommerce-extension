<?php

declare(strict_types=1);

namespace Twint\Woo\Helper;

use DOMDocument;

class XmlHelper
{
    public static function format($xml): bool|string
    {
        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = true;
        $dom->loadXML($xml);
        return $dom->saveXML();
    }
}
