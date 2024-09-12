<?php

namespace Twint\Woo\Container;

interface ContainerInterface
{
    /**
     * Get registered container by id
     *
     * @throw
     * @param string $id
     * @return mixed
     */
    public function get(string $id): mixed;

    public function has(string $id): bool;
}
