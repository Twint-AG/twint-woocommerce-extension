<?php declare(strict_types=1);
namespace Gzp\WbsNg;

use Gzp\WbsNg\Mapping\Context;
use Gzp\WbsNg\Model\Calc\Solution;
use Throwable;


class SolutionMeta
{
    public const Key = 'wbsng_solution';

    public static function serialize(Solution $solution): string
    {
        return json_encode($solution->serialize());
    }

    public static function unserialize($json, string &$error = null): ?Solution
    {
        if ($json === null || $json === '' || $json === false) {
            return null;
        }

        $ctx = Context::of($json);
        try {

            $data = $ctx->map(function($x) {

                // php pre 7.3
                if (!defined('JSON_THROW_ON_ERROR')) {
                    $r = json_decode($x, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new \RuntimeException(json_last_error_msg(), json_last_error());
                    }
                    return $r;
                }

                return json_decode($x, true, 512, JSON_THROW_ON_ERROR);
            });

            return Solution::unserialize($data);

        } catch (Throwable $e) {
            $dataStr = var_export($json, true);
            $error =
                "shipping breakdown unserializing at {$ctx->origin($e)->path()}: {$e->getMessage()}\n".
                "DATA: $dataStr\n".
                "{$e->getTraceAsString()}";
            return null;
        }
    }
}