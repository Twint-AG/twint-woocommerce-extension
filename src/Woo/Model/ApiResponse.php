<?php

declare(strict_types=1);

namespace Twint\Woo\Model;

class ApiResponse
{
    public function __construct(
        private readonly mixed $return,
        private readonly array $log
    )
    {
    }

    public function getLog(): array
    {
        return $this->log;
    }

    public function getReturn(): mixed
    {
        return $this->return;
    }
}
