<?php

namespace Twint\Woo\Container;

use Twint\Woo\Container\Exception\ContainerException;

class AliasingContainer implements ContainerInterface
{
    public function __construct(private array $alias = [])
    {
    }

    /**
     * @throws ContainerException
     */
    public function get(string $id): mixed
    {
        if (!$this->has($id)) {
            throw new ContainerException("Container $id is not registered");
        }

        $item = $this->alias[$id];

        if (is_callable($item)) {
            $this->alias[$id] = $item($this);
        }

        return $this->alias[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->alias[$id]);
    }
}
