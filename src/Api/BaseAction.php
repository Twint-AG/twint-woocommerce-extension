<?php

declare(strict_types=1);

namespace Twint\Woo\Api;

abstract class BaseAction
{
    public function requireLogin(): void
    {
        echo 'You must login to do next actions';
        die();
    }
}
