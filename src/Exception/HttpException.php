<?php

declare(strict_types=1);

namespace Twint\Woo\Exception;

use RuntimeException;
use Throwable;

class HttpException extends RuntimeException implements HttpExceptionInterface
{
    private int $statusCode;

    private array $headers;

    public function __construct(int $statusCode, string $message = '', ?Throwable $previous = null, array $headers = [], int $code = 0)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;

        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }
}
