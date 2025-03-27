<?php declare(strict_types=1);

namespace Gzp\WbsNg;


class WpTools
{
    public static function removeScripts(array $regexes, $keepAssetsStartingWithUrl = null)
    {
        global $wp_scripts;

        $url = $keepAssetsStartingWithUrl;

        foreach ($wp_scripts->registered as $dep) {
            if (($src = (string)@$dep->src) !== '')
                if (!isset($url) || substr_compare($src, $url, 0, strlen($url)) !== 0) {
                    foreach ($regexes as $regex) {
                        if (preg_match($regex, $src)) {
                            $wp_scripts->remove($dep->handle);
                            break;
                        }
                    }
                }
        }
    }

    public static function addActionOrCall($action, $callback, $priority = 10, $acceptedArgs = 1): void
    {
        if (did_action($action)) {
            /** @noinspection VariableFunctionsUsageInspection */
            call_user_func($callback);
        }
        else {
            add_action($action, $callback, $priority, $acceptedArgs);
        }
    }
}