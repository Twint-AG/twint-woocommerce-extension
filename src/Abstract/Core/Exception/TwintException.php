<?php declare(strict_types=1);

namespace TWINT\Abstract\Core\Exception;

interface TwintException extends \Throwable
{
    public function getErrorCode(): string;

    /**
     * @return array<string|int, mixed|null>
     */
    public function getParameters(): array;
}
