<?php

namespace Lonate\Core\Http;

use Lonate\Core\Foundation\Application;

/**
 * Class Router
 * 
 * Handles URL routing and dispatching to controllers/closures.
 * Supports route parameters (e.g., /users/{id}).
 * 
 * @package Lonate\Core\Http
 */
class Router
{
    protected Application $app;

    protected array $routes = [
        'GET'    => [],
        'POST'   => [],
        'PUT'    => [],
        'DELETE' => [],
        'PATCH'  => [],
    ];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Load routes from a file.
     * 
     * @param string $path
     */
    public function load(string $path): void
    {
        if (file_exists($path)) {
            $router = $this;
            require $path;
        }
    }

    // =============================
    // Route Registration
    // =============================

    public function get(string $uri, callable|array|string $action): void
    {
        $this->addRoute('GET', $uri, $action);
    }

    public function post(string $uri, callable|array|string $action): void
    {
        $this->addRoute('POST', $uri, $action);
    }

    public function put(string $uri, callable|array|string $action): void
    {
        $this->addRoute('PUT', $uri, $action);
    }

    public function patch(string $uri, callable|array|string $action): void
    {
        $this->addRoute('PATCH', $uri, $action);
    }

    public function delete(string $uri, callable|array|string $action): void
    {
        $this->addRoute('DELETE', $uri, $action);
    }

    protected function addRoute(string $method, string $uri, mixed $action): void
    {
        $uri = trim($uri, '/');
        if ($uri === '') {
            $uri = '/';
        }
        $this->routes[$method][$uri] = $action;
    }

    // =============================
    // Dispatching
    // =============================

    /**
     * Dispatch the request to the appropriate route.
     *
     * @param Request $request
     * @return mixed
     */
    public function dispatch(Request $request): mixed
    {
        $method = $request->method();
        $uri = trim($request->uri(), '/');
        if ($uri === '') {
            $uri = '/';
        }

        // 1. Exact match
        if (isset($this->routes[$method][$uri])) {
            return $this->resolveAction($this->routes[$method][$uri], $request);
        }

        // 2. Try parameterized routes
        foreach ($this->routes[$method] as $pattern => $action) {
            if (str_contains($pattern, '{')) {
                $match = $this->matchParameterizedRoute($pattern, $uri);
                if ($match !== null) {
                    $request->setParams($match);
                    return $this->resolveAction($action, $request);
                }
            }
        }

        return new Response("404 - Not Found", 404);
    }

    /**
     * Match a parameterized route pattern against a URI.
     * 
     * @param string $pattern e.g. "api/users/{id}"
     * @param string $uri e.g. "api/users/42"
     * @return array|null Matched parameters or null
     */
    protected function matchParameterizedRoute(string $pattern, string $uri): ?array
    {
        $patternParts = explode('/', $pattern);
        $uriParts = explode('/', $uri);

        if (count($patternParts) !== count($uriParts)) {
            return null;
        }

        $params = [];
        foreach ($patternParts as $i => $part) {
            if (preg_match('/^\{(\w+)\}$/', $part, $m)) {
                $params[$m[1]] = $uriParts[$i];
            } elseif ($part !== $uriParts[$i]) {
                return null;
            }
        }

        return $params;
    }

    /**
     * Resolve and execute a route action.
     *
     * @param mixed $action
     * @param Request $request
     * @return mixed
     */
    protected function resolveAction(mixed $action, Request $request): mixed
    {
        if (is_callable($action)) {
            return call_user_func($action, $request);
        }

        if (is_array($action)) {
            [$controller, $method] = $action;
            $instance = $this->app->make($controller);
            return $instance->$method($request);
        }

        return $action;
    }

    /**
     * Get all registered routes (for debugging/listing).
     *
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
