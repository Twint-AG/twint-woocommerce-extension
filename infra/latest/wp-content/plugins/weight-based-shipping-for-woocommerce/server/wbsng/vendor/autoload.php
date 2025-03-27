<?php declare(strict_types=1);

(static function() {

    $flag = __FILE__;
    if (isset($GLOBALS[$flag])) return;
    $GLOBALS[$flag] = true;


    $filesFile = __DIR__.'/composer/autoload_files.php';
    if (file_exists($filesFile)) {
        $files = require($filesFile);
        foreach ($files as $f) {
            require($f);
        }
    }

    $classes = require(__DIR__.'/composer/autoload_classmap.php');
    spl_autoload_register(function($class) use ($classes): void {
        $file = $classes[$class] ?? null;
        if (isset($file)) {
            require($file);
        }
    });
})();