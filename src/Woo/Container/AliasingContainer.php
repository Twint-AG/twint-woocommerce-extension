<?php

declare(strict_types=1);

namespace Twint\Woo\Container;

use Twint\Woo\Container\Exception\ContainerException;

class AliasingContainer implements ContainerInterface
{
    public function __construct(
        private array $alias = []
    ) {
    }

    /**
     * @throws ContainerException
     */
    public function get(string $id, bool $immediately = false): mixed
    {
        if (!$this->has($id)) {
            throw new ContainerException("Container '{$id}' is not registered");
        }

        $instance = $this->alias[$id];

        // Handle if it's callable
        $instance = is_callable($instance) ? $instance($this) : $instance;

        // Handle Lazy instance logic
        if ($instance instanceof Lazy) {
            $instance->setId($id);

            if ($immediately) {
                $instance = $instance->get();
            }
        }

        // Assign back and return
        $this->alias[$id] = $instance;

        return $instance;

    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->alias);
    }
}
