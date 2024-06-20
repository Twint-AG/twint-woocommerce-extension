<?php

namespace TWINT\Utility;

use PeterHegman\Dumper;

class Dump
{
    /**
     * @param mixed ...$args
     */
    public static function die(...$args)
    {
        array_map(function ($x) {
            (new Dumper())->dump($x);
        }, func_get_args());

        die();
    }

    /**
     * @param mixed ...$args
     */
    public static function data(...$args)
    {
        var_export(...$args);
    }
}