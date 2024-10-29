<?php

declare(strict_types=1);

namespace Twint\Woo\Exception;

use Throwable;

/**
 * Interface for HTTP error exceptions.
 */
interface HttpExceptionInterface extends Throwable
{
    /**
     * Returns the status code.
     */
    public function getStatusCode(): int;

    /**
     * Returns response headers.
     */
    public function getHeaders(): array;
}
