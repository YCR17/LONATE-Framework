<?php

namespace Lonate\Core\Http;

/**
 * Class Response
 * 
 * Represents an HTTP response with content, status code, and headers.
 * 
 * @package Lonate\Core\Http
 */
class Response
{
    protected mixed $content;
    protected int $status;
    protected array $headers;

    public function __construct(mixed $content = '', int $status = 200, array $headers = [])
    {
        $this->content = $content;
        $this->status = $status;
        $this->headers = $headers;
    }

    /**
     * Create a JSON response.
     *
     * @param mixed $data
     * @param int $status
     * @param array $headers
     * @return static
     */
    public static function json(mixed $data, int $status = 200, array $headers = []): static
    {
        $headers['Content-Type'] = 'application/json';
        return new static(json_encode($data, JSON_UNESCAPED_UNICODE), $status, $headers);
    }

    /**
     * Set a header for the response.
     * 
     * @param string $key
     * @param string $value
     * @return static
     */
    public function setHeader(string $key, string $value): static
    {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * Set the HTTP status code.
     *
     * @param int $code
     * @return static
     */
    public function setStatusCode(int $code): static
    {
        $this->status = $code;
        return $this;
    }

    /**
     * Get the response content.
     *
     * @return mixed
     */
    public function getContent(): mixed
    {
        return $this->content;
    }

    /**
     * Get the HTTP status code.
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->status;
    }

    /**
     * Send the response to the browser.
     *
     * @return void
     */
    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $key => $value) {
                header("{$key}: {$value}");
            }
        }

        echo $this->content;
    }
}
