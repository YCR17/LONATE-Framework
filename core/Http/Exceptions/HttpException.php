<?php

namespace Lonate\Core\Http\Exceptions;

/**
 * HttpException — thrown by abort() helper.
 */
class HttpException extends \RuntimeException
{
    protected int $statusCode;
    protected array $headers;

    public function __construct(int $statusCode, string $message = '', array $headers = [], ?\Throwable $previous = null)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;

        if ($message === '') {
            $message = match ($statusCode) {
                400 => 'Bad Request',
                401 => 'Unauthorized',
                403 => 'Forbidden',
                404 => 'Not Found',
                405 => 'Method Not Allowed',
                419 => 'Page Expired',
                422 => 'Unprocessable Entity',
                429 => 'Too Many Requests',
                500 => 'Internal Server Error',
                503 => 'Service Unavailable',
                default => 'HTTP Error',
            };
        }

        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }
}
