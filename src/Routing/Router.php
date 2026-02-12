<?php

namespace Aksa\Routing;

use Closure;
use Aksa\Http\Request;
use Aksa\Http\Response;

class Router
{
    protected $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'DELETE' => [],
        'PATCH' => []
    ];
    
    protected $middlewares = [];
    protected $currentMiddleware = [];
    protected $currentPrefix = '';
    protected $middlewareAliases = [];
    protected $middlewareGroups = [];
    
    public function get($uri, $action)
    {
        return $this->addRoute('GET', $uri, $action);
    }
    
    public function post($uri, $action)
    {
        return $this->addRoute('POST', $uri, $action);
    }
    
    public function put($uri, $action)
    {
        return $this->addRoute('PUT', $uri, $action);
    }
    
    public function delete($uri, $action)
    {
        return $this->addRoute('DELETE', $uri, $action);
    }
    
    public function patch($uri, $action)
    {
        return $this->addRoute('PATCH', $uri, $action);
    }
    
    protected function addRoute($method, $uri, $action)
    {
        $uri = '/' . trim($uri, '/');

        // apply current prefix
        $prefix = '/' . trim($this->currentPrefix, '/');
        if ($prefix === '/') $prefix = '';

        // avoid double prefixing if uri already contains prefix
        if ($prefix && strpos($uri, $prefix) === 0) {
            $fullUri = $uri;
        } else {
            $fullUri = $prefix . $uri;
        }

        if ($fullUri === '') $fullUri = '/';

        $this->routes[$method][$fullUri] = [
            'action' => $action,
            'middleware' => $this->currentMiddleware
        ];

        // reset middleware for next route
        $this->currentMiddleware = [];

        return $this;
    }
    
    public function middleware($middleware)
    {
        $this->currentMiddleware = is_array($middleware) ? $middleware : [$middleware];
        return $this;
    }
    
    public function prefix($prefix)
    {
        $this->currentPrefix = trim($prefix, '/');
        return $this;
    }

    public function group($attributes, \Closure $callback = null)
    {
        // support calling group(function() { ... }) after chaining (e.g., Route::middleware('auth')->group(...))
        if ($attributes instanceof \Closure && $callback === null) {
            $callback = $attributes;
            $attributes = [];
        }

        $previousMiddleware = $this->currentMiddleware;
        $previousPrefix = $this->currentPrefix;

        if (isset($attributes['middleware'])) {
            $middleware = is_array($attributes['middleware'])
                ? $attributes['middleware']
                : [$attributes['middleware']];
            $this->currentMiddleware = array_merge($this->currentMiddleware, $middleware);
        }

        if (isset($attributes['prefix'])) {
            $this->currentPrefix = trim($previousPrefix . '/' . trim($attributes['prefix'], '/'), '/');
        }

        $callback($this);

        $this->currentMiddleware = $previousMiddleware;
        $this->currentPrefix = $previousPrefix;
    }
    
    public function dispatch(Request $request)
    {
        $method = $request->method();
        $uri = '/' . trim($request->uri(), '/');

        foreach ($this->routes[$method] as $routeUri => $route) {
            $pattern = $this->convertToRegex($routeUri);

            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);

                $request->setRouteParameters($matches);

                // If route has no middleware explicitly, determine default by request path
                $routeMiddleware = $route['middleware'];
                if (empty($routeMiddleware)) {
                    if (strpos($uri, '/api') === 0) {
                        $routeMiddleware = ['api'];
                    } else {
                        $routeMiddleware = ['web'];
                    }
                }

                // expand middleware names (aliases or groups) into class names
                $pipelineMiddlewares = $this->expandMiddleware($routeMiddleware);

                // Prepare the final action
                $final = function($request) use ($route, $matches) {
                    return $this->callAction($route['action'], $matches, $request);
                };

                // Build middleware pipeline
                $pipeline = array_reduce(
                    array_reverse($pipelineMiddlewares),
                    function($next, $middleware) {
                        // $middleware may be Closure or class name
                        if ($middleware instanceof Closure) {
                            return function($request) use ($middleware, $next) {
                                return $middleware($request, $next);
                            };
                        }

                        return function($request) use ($middleware, $next) {
                            $instance = new $middleware;
                            return $instance->handle($request, $next);
                        };
                    },
                    $final
                );

                return $pipeline($request);
            }
        }

        return new Response('404 Not Found', 404);
    }
    
    protected function convertToRegex($uri)
    {
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $uri);
        return '#^' . $pattern . '$#';
    }

    /**
     * Register a middleware alias (e.g., 'auth' => App\Middleware\AuthMiddleware::class)
     */
    public function registerMiddlewareAlias($alias, $class = null)
    {
        // Accept ['name' => 'Class', ...] or ('name', 'Class')
        if (is_array($alias)) {
            foreach ($alias as $a => $c) {
                $this->middlewareAliases[$a] = $c;
            }
            return;
        }

        $this->middlewareAliases[$alias] = $class;
    }

    /**
     * Register a middleware group (e.g., 'web' => ['cors', ...])
     */
    public function registerMiddlewareGroup($name, array $middlewares)
    {
        $this->middlewareGroups[$name] = $middlewares;
    }

    protected function expandMiddleware(array $middlewares)
    {
        $result = [];

        foreach ($middlewares as $m) {
            // group
            if (isset($this->middlewareGroups[$m])) {
                foreach ($this->middlewareGroups[$m] as $sub) {
                    $result = array_merge($result, $this->expandMiddleware(is_array($sub) ? $sub : [$sub]));
                }
                continue;
            }

            // alias
            if (isset($this->middlewareAliases[$m])) {
                $result[] = $this->middlewareAliases[$m];
                continue;
            }

            // already class name
            $result[] = $m;
        }

        return $result;
    }
    
    protected function callAction($action, $parameters, Request $request)
    {
        if (is_callable($action)) {
            return call_user_func_array($action, array_merge([$request], $parameters));
        }
        
        if (is_string($action)) {
            list($controller, $method) = explode('@', $action);

            // prefer App\Http\Controllers namespace (Laravel-style)
            $controllerClass = "App\\Http\\Controllers\\{$controller}";

            if (!class_exists($controllerClass)) {
                // fallback to legacy App\Controllers for backward-compatibility
                $controllerClass = "App\\Controllers\\{$controller}";
            }

            if (!class_exists($controllerClass)) {
                throw new \Exception("Controller {$controllerClass} not found");
            }

            $controllerInstance = new $controllerClass;

            if (!method_exists($controllerInstance, $method)) {
                throw new \Exception("Method {$method} not found in {$controllerClass}");
            }

            return call_user_func_array(
                [$controllerInstance, $method],
                array_merge([$request], $parameters)
            );
        }
        
        throw new \Exception("Invalid route action");
    }
}
