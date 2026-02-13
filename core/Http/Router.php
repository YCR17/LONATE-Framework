<?php

namespace Lonate\Core\Http;

use Lonate\Core\Foundation\Application;

/**
 * Class Router
 * 
 * Handles URL routing and dispatching to controllers/closures.
 * Supports route parameters (e.g., /users/{id}).
 * Supports string action ('Controller@method') and array syntax.
 * Supports named routes for URL generation.
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

    /** @var array Named routes: name => ['method' => ..., 'uri' => ...] */
    protected array $namedRoutes = [];

    /** @var string|null The name to assign to the next registered route */
    protected ?string $pendingName = null;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Load routes from a file.
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

    public function get(string $uri, callable|array|string $action): static
    {
        $this->addRoute('GET', $uri, $action);
        return $this;
    }

    public function post(string $uri, callable|array|string $action): static
    {
        $this->addRoute('POST', $uri, $action);
        return $this;
    }

    public function put(string $uri, callable|array|string $action): static
    {
        $this->addRoute('PUT', $uri, $action);
        return $this;
    }

    public function patch(string $uri, callable|array|string $action): static
    {
        $this->addRoute('PATCH', $uri, $action);
        return $this;
    }

    public function delete(string $uri, callable|array|string $action): static
    {
        $this->addRoute('DELETE', $uri, $action);
        return $this;
    }

    /**
     * Register a route that responds to any HTTP method.
     */
    public function any(string $uri, callable|array|string $action): static
    {
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $this->addRoute($method, $uri, $action);
        }
        return $this;
    }

    /**
     * Register a route for specific methods.
     */
    public function match(array $methods, string $uri, callable|array|string $action): static
    {
        foreach ($methods as $method) {
            $this->addRoute(strtoupper($method), $uri, $action);
        }
        return $this;
    }

    /**
     * Assign a name to the most recently registered route, or set pending name for next.
     */
    public function name(string $name): static
    {
        // If there are routes already, name the last one
        // Otherwise, store as pending for next addRoute
        $this->pendingName = $name;
        return $this;
    }

    protected function addRoute(string $method, string $uri, mixed $action): void
    {
        $uri = trim($uri, '/');
        if ($uri === '') {
            $uri = '/';
        }
        $this->routes[$method][$uri] = $action;

        // Handle pending name
        if ($this->pendingName) {
            $this->namedRoutes[$this->pendingName] = ['method' => $method, 'uri' => $uri];
            $this->pendingName = null;
        }
    }

    // =============================
    // Named Route URL Generation
    // =============================

    /**
     * Generate URL for a named route.
     */
    public function route(string $name, array $parameters = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \RuntimeException("Route [{$name}] not defined.");
        }

        $uri = $this->namedRoutes[$name]['uri'];

        // Replace route parameters
        foreach ($parameters as $key => $value) {
            $uri = str_replace("{{$key}}", $value, $uri);
        }

        return '/' . ltrim($uri, '/');
    }

    // =============================
    // Dispatching
    // =============================

    /**
     * Dispatch the request to the appropriate route.
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
     * Supports:
     * - Closures/callables
     * - Array: [Controller::class, 'method']
     * - String: 'App\Http\Controllers\MyController@index'
     */
    protected function resolveAction(mixed $action, Request $request): mixed
    {
        // Closure / callable
        if (is_callable($action) && !is_string($action)) {
            return call_user_func($action, $request);
        }

        // Array: [Controller::class, 'method']
        if (is_array($action)) {
            [$controller, $method] = $action;
            $instance = $this->app->make($controller);
            return $instance->$method($request);
        }

        // String: 'Controller@method'
        if (is_string($action) && str_contains($action, '@')) {
            [$controller, $method] = explode('@', $action, 2);
            $instance = $this->app->make($controller);
            return $instance->$method($request);
        }

        // String without @ — just return it (simple string response)
        return $action;
    }

    /**
     * Get all registered routes (for debugging/listing).
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Get all named routes.
     */
    public function getNamedRoutes(): array
    {
        return $this->namedRoutes;
    }
}
