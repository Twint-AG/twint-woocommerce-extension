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
    public function get(string $id): mixed
    {
        if (!$this->has($id)) {
            throw new ContainerException("Container '{$id}' is not registered");
        }

        $this->alias[$id] = is_callable($this->alias[$id])
            ? ($this->alias[$id])($this)
            : $this->alias[$id];

        if($this->alias[$id] instanceof  Lazy){
            $this->alias[$id]->setId($id);
        }

        return $this->alias[$id];
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->alias);
    }
}
