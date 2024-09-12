<?php

namespace Twint\Woo\Api;

use JetBrains\PhpStorm\NoReturn;

abstract class BaseAction
{
    #[NoReturn] public function requireLogin(): void
    {
        echo 'You must login to do next actions';
        die();
    }
}
