<?php

declare(strict_types=1);

namespace Twint\Woo\Api;

use JetBrains\PhpStorm\NoReturn;

abstract class BaseAction
{
    #[NoReturn]
    public function requireLogin(): void
    {
        echo 'You must login to do next actions';
        die();
    }
}
