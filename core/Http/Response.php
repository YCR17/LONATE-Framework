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
     */
    public static function json(mixed $data, int $status = 200, array $headers = []): static
    {
        $headers['Content-Type'] = 'application/json';
        return new static(json_encode($data, JSON_UNESCAPED_UNICODE), $status, $headers);
    }

    /**
     * Create a redirect response.
     */
    public static function redirect(string $url, int $status = 302): static
    {
        return new static('', $status, ['Location' => $url]);
    }

    /**
     * Create a "no content" response.
     */
    public static function noContent(int $status = 204, array $headers = []): static
    {
        return new static('', $status, $headers);
    }

    /**
     * Set a single header.
     */
    public function header(string $key, string $value): static
    {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * Alias for header().
     */
    public function setHeader(string $key, string $value): static
    {
        return $this->header($key, $value);
    }

    /**
     * Set multiple headers at once.
     */
    public function withHeaders(array $headers): static
    {
        foreach ($headers as $key => $value) {
            $this->headers[$key] = $value;
        }
        return $this;
    }

    /**
     * Set the HTTP status code.
     */
    public function setStatusCode(int $code): static
    {
        $this->status = $code;
        return $this;
    }

    /**
     * Set the response content.
     */
    public function setContent(mixed $content): static
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Get the response content.
     */
    public function getContent(): mixed
    {
        return $this->content;
    }

    /**
     * Get the HTTP status code.
     */
    public function getStatusCode(): int
    {
        return $this->status;
    }

    /**
     * Get all headers.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get a specific header value.
     */
    public function getHeader(string $key): ?string
    {
        return $this->headers[$key] ?? null;
    }

    /**
     * Check if a header exists.
     */
    public function hasHeader(string $key): bool
    {
        return isset($this->headers[$key]);
    }

    /**
     * Create a download response.
     */
    public static function download(string $file, ?string $name = null, array $headers = []): static
    {
        $name = $name ?? basename($file);
        $headers['Content-Disposition'] = "attachment; filename=\"{$name}\"";
        $headers['Content-Type'] = 'application/octet-stream';
        
        $content = file_exists($file) ? file_get_contents($file) : '';
        return new static($content, 200, $headers);
    }

    /**
     * Is this a successful response?
     */
    public function isSuccessful(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    /**
     * Is this a redirect response?
     */
    public function isRedirect(): bool
    {
        return in_array($this->status, [301, 302, 303, 307, 308]);
    }

    /**
     * Is this a client error?
     */
    public function isClientError(): bool
    {
        return $this->status >= 400 && $this->status < 500;
    }

    /**
     * Is this a server error?
     */
    public function isServerError(): bool
    {
        return $this->status >= 500 && $this->status < 600;
    }

    /**
     * Send the response to the browser.
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
