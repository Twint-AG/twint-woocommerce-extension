<?php
namespace Wbs;


class WpTools
{
    static public function removeScripts(array $regexes, $keepAssetsStartingWithUrl = null)
    {
        global $wp_scripts;

        $url = $keepAssetsStartingWithUrl;

        /** @var \_WP_Dependency $dep */
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

    static public function addActionOrCall($action, $callback, $priority = 10, $acceptedArgs = 1)
    {
        if (did_action($action)) {
            call_user_func($callback);
        } else {
            add_action($action, $callback, $priority, $acceptedArgs);
        }
    }
}