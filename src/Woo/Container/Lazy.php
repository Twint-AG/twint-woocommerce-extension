<?php

declare(strict_types=1);

namespace Twint\Woo\Container;

use Twint\Plugin;

class Lazy
{
    private string $id;

    private $resolver;

    public function __construct(callable $resolver)
    {
        $this->resolver = $resolver;
    }

    public function setId(string $value): void
    {
        $this->id = $value;
    }

    public function get(): mixed
    {
        $instance = Plugin::di($this->id);
        if ($instance instanceof self) {
            return ($this->resolver)();
        }

        return $instance;
    }
}
