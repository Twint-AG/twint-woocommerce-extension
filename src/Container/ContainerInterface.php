<?php

declare(strict_types=1);

namespace Twint\Woo\Container;

interface ContainerInterface
{
    /**
     * Get registered container by id
     *
     * @throw
     */
    public function get(string $id, bool $lazyLoad = true): mixed;

    public function has(string $id): bool;
}
