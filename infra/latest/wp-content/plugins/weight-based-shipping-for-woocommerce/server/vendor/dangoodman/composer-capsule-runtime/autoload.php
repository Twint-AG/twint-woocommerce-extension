<?php
require_once(__DIR__.'/src/Runtime.php');
require_once(__DIR__.'/src/Wrapper.php');
require_once(__DIR__.'/src/CCR.php');

return function($remappedNamesRegistryFile)
{
    /** @noinspection PhpIncludeInspection */
    $capsuled = require($remappedNamesRegistryFile);

    WbsVendors_CCR::$instance = new \WbsVendors\Dgm\ComposerCapsule\Runtime\Runtime(
        new \WbsVendors\Dgm\ComposerCapsule\Runtime\Wrapper(
            $capsuled['capsule'],
            $capsuled['uncapsule']
        )
    );
};