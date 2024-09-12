<?php declare(strict_types=1);

namespace Twint\Woo\Exception;

interface TwintException extends \Throwable
{
    public function getErrorCode(): string;

    /**
     * @return array<string|int, mixed|null>
     */
    public function getParameters(): array;
}
