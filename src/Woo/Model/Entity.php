<?php

declare(strict_types=1);

namespace Twint\Woo\Model;

abstract class Entity
{
    private bool $isNewRecord = true;

    public function __construct(bool $isNewRecord = true)
    {
        $this->isNewRecord = $isNewRecord;
    }

    public function isNewRecord(): bool
    {
        return $this->isNewRecord;
    }

    protected function mapping(): array
    {
        return [];
    }

    // Constructor
    public function load($data = []): self
    {
        $map = $this->mapping();

        foreach ($data as $key => $value) {
            if (array_key_exists($key, $map)) {
                $field = $map[$key];
                if (is_array($field)) {
                    $value = $field[1]($value);
                    $field = $field[0];
                }

                $this->{$field} = $value;
            }
        }

        return $this;
    }
}
