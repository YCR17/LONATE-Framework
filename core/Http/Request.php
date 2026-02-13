<?php

namespace Lonate\Core\Http;

/**
 * Class Request
 * 
 * Represents an incoming HTTP request.
 * Encapsulates query parameters, post data, server variables,
 * headers, and route parameters.
 * 
 * @package Lonate\Core\Http
 */
class Request
{
    protected array $query;
    protected array $request;
    protected array $server;
    protected array $params = [];

    public function __construct(array $query = [], array $request = [], array $server = [])
    {
        $this->query = $query;
        $this->request = $request;
        $this->server = $server;
    }

    /**
     * Capture the current HTTP request from PHP globals.
     *
     * @return static
     */
    public static function capture(): static
    {
        return new static($_GET, $_POST, $_SERVER);
    }

    /**
     * Get a value from the input (POST first, then GET).
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function input(string $key, mixed $default = null): mixed
    {
        // Also check JSON body
        $json = $this->json();
        return $this->request[$key] ?? $this->query[$key] ?? $json[$key] ?? $default;
    }

    /**
     * Get all input data (merged query + post + json).
     *
     * @return array
     */
    public function all(): array
    {
        return array_merge($this->query, $this->request, $this->json());
    }

    /**
     * Get the raw JSON body as an array.
     *
     * @return array
     */
    public function json(): array
    {
        static $parsed = null;
        if ($parsed === null) {
            $body = file_get_contents('php://input');
            if ($body) {
                $parsed = json_decode($body, true) ?? [];
            } else {
                $parsed = [];
            }
        }
        return $parsed;
    }

    /**
     * Get the request method.
     *
     * @return string
     */
    public function method(): string
    {
        return $this->server['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Get the request URI path.
     *
     * @return string
     */
    public function uri(): string
    {
        return parse_url($this->server['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    }

    /**
     * Get the full URL.
     *
     * @return string
     */
    public function fullUrl(): string
    {
        $scheme = ($this->server['HTTPS'] ?? 'off') === 'on' ? 'https' : 'http';
        $host = $this->server['HTTP_HOST'] ?? 'localhost';
        $uri = $this->server['REQUEST_URI'] ?? '/';
        return "{$scheme}://{$host}{$uri}";
    }

    /**
     * Get a specific HTTP header.
     *
     * @param string $key e.g. 'Content-Type', 'Accept'
     * @param mixed $default
     * @return mixed
     */
    public function header(string $key, mixed $default = null): mixed
    {
        // HTTP headers in $_SERVER are prefixed with HTTP_ and uppercased
        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        
        // Content-Type and Content-Length don't have HTTP_ prefix
        if (in_array(strtoupper(str_replace('-', '_', $key)), ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
            $serverKey = strtoupper(str_replace('-', '_', $key));
        }

        return $this->server[$serverKey] ?? $default;
    }

    /**
     * Check if the request expects a JSON response.
     *
     * @return bool
     */
    public function expectsJson(): bool
    {
        $accept = $this->header('Accept', '');
        return str_contains($accept, 'application/json') || $this->isJson();
    }

    /**
     * Check if the request content type is JSON.
     *
     * @return bool
     */
    public function isJson(): bool
    {
        $contentType = $this->header('Content-Type', '');
        return str_contains($contentType, 'application/json');
    }

    /**
     * Check if the request is an AJAX request.
     *
     * @return bool
     */
    public function ajax(): bool
    {
        return ($this->server['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    /**
     * Set route parameters (called by Router).
     *
     * @param array $params
     * @return void
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    /**
     * Get a route parameter by name.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    /**
     * Get all route parameters.
     *
     * @return array
     */
    public function params(): array
    {
        return $this->params;
    }

    /**
     * Get the client IP address.
     *
     * @return string
     */
    public function ip(): string
    {
        return $this->server['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Check if the request method matches.
     *
     * @param string $method
     * @return bool
     */
    public function isMethod(string $method): bool
    {
        return strtoupper($this->method()) === strtoupper($method);
    }
}
