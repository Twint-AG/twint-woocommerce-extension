<?php

declare(strict_types=1);

namespace Twint\Woo\Exception;

use Generator;
use Throwable;
use WP_Http;
use function is_array;

abstract class TwintHttpException extends HttpException implements TwintException
{
    /**
     * @var array<string, mixed>
     */
    protected $parameters = [];

    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(
        string $message,
        array $parameters = [],
        ?Throwable $e = null
    ) {
        $this->parameters = $parameters;
        $message = $this->parse($message, $parameters);

        parent::__construct($this->getStatusCode(), $message, $e);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    protected function parse(string $message, array $parameters = []): string
    {
        $regex = [];

        foreach ($parameters as $key => $value) {
            if (is_array($value)) {
                continue;
            }

            $formattedKey = preg_replace('/[^a-z]/i', '', $key);
            $regex[sprintf('/\{\{(\s+)?(%s)(\s+)?\}\}/', $formattedKey)] = $value;
        }

        return (string) preg_replace(array_keys($regex), array_values($regex), $message);
    }

    public function getStatusCode(): int
    {
        return WP_Http::INTERNAL_SERVER_ERROR;
    }

    public function getErrors(bool $withTrace = false): Generator
    {
        yield $this->getCommonErrorData($withTrace);
    }

    protected function getCommonErrorData(bool $withTrace = false): array
    {
        $error = [
            'status' => (string) $this->getStatusCode(),
            'code' => $this->getErrorCode(),
            'title' => $this->getStatusCode() ?? 'unknown status',
            'detail' => $this->getMessage(),
            'meta' => [
                'parameters' => $this->getParameters(),
            ],
        ];

        if ($withTrace) {
            $error['trace'] = $this->getTrace();
        }

        return $error;
    }

    /**
     * @return array<string, mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return mixed|null
     */
    public function getParameter(string $key)
    {
        return $this->parameters[$key] ?? null;
    }
}
