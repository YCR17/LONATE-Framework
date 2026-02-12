<?php

namespace Aksa\Support;

use Aksa\Routing\Router;
use Aksa\Http\Request;
use Aksa\Http\Response;
use Aksa\Database\DatabaseManager;

class Application
{
    protected $basePath;
    protected $bindings = [];
    protected $instances = [];
    protected $middlewareAliases = [];
    protected $middlewareGroups = [];
    protected $routeConfig = [];
    protected $exceptionHandlerClass = null;

    public function __construct($basePath)
    {
        $this->basePath = $basePath;
        $this->registerBaseBindings();
    }

    public static function configure($basePath)
    {
        return new static($basePath);
    }

    /**
     * Configure routing.
     * Accepts either an associative array or named parameters for convenience.
     * Examples:
     *  ->withRouting(['web' => ..., 'api' => ...])
     *  ->withRouting(web: 'routes/web.php', api: 'routes/api.php')
     */
    public function withRouting($web = null, $commands = null, $api = null, $health = null)
    {
        // Backward compatible: array input
        if (is_array($web)) {
            $this->routeConfig = $web;
            return $this;
        }

        $routes = [];
        if ($web) $routes['web'] = $web;
        if ($commands) $routes['commands'] = $commands;
        if ($api) $routes['api'] = $api;
        if ($health) $routes['health'] = $health;

        $this->routeConfig = $routes;
        return $this;
    }

    public function withMiddleware(callable $callback)
    {
        $registrar = new MiddlewareRegistrar($this);
        $callback($registrar);
        return $this;
    }

    public function withExceptions(callable $callback)
    {
        $config = new ExceptionsConfigurator($this);
        $callback($config);
        return $this;
    }

    public function create()
    {
        // perform any final initialization if needed
        return $this;
    }

    // Middleware / Exceptions registration helpers used by registrars
    public function registerMiddlewareAlias($alias, $class = null)
    {
        // Accept registerMiddlewareAlias('name', 'Class') or registerMiddlewareAlias(['name' => 'Class'])
        if (is_array($alias)) {
            foreach ($alias as $a => $c) {
                $this->middlewareAliases[$a] = $c;
            }
            return $this;
        }

        $this->middlewareAliases[$alias] = $class;
        return $this;
    }

    public function registerMiddlewareGroup($name, array $middlewares)
    {
        $this->middlewareGroups[$name] = $middlewares;
        return $this;
    }

    public function setExceptionHandlerClass($class)
    {
        $this->exceptionHandlerClass = $class;
        return $this;
    }
    
    protected function registerBaseBindings()
    {
        static::setInstance($this);
        $this->instance('app', $this);
    }
    
    public function singleton($abstract, $concrete = null)
    {
        $this->bindings[$abstract] = [
            'concrete' => $concrete ?? $abstract,
            'shared' => true
        ];
    }
    
    public function bind($abstract, $concrete = null)
    {
        $this->bindings[$abstract] = [
            'concrete' => $concrete ?? $abstract,
            'shared' => false
        ];
    }
    
    public function instance($abstract, $instance)
    {
        $this->instances[$abstract] = $instance;
        return $instance;
    }
    
    public function make($abstract)
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }
        
        if (!isset($this->bindings[$abstract])) {
            return $this->build($abstract);
        }
        
        $concrete = $this->bindings[$abstract]['concrete'];
        
        if ($this->bindings[$abstract]['shared']) {
            $object = $this->build($concrete);
            $this->instances[$abstract] = $object;
            return $object;
        }
        
        return $this->build($concrete);
    }
    
    protected function build($concrete)
    {
        if ($concrete instanceof \Closure) {
            return $concrete($this);
        }
        
        $reflector = new \ReflectionClass($concrete);
        
        if (!$reflector->isInstantiable()) {
            throw new \Exception("Class {$concrete} is not instantiable");
        }
        
        $constructor = $reflector->getConstructor();
        
        if (is_null($constructor)) {
            return new $concrete;
        }
        
        $dependencies = $constructor->getParameters();
        $instances = $this->resolveDependencies($dependencies);
        
        return $reflector->newInstanceArgs($instances);
    }
    
    protected function resolveDependencies($dependencies)
    {
        $results = [];
        
        foreach ($dependencies as $dependency) {
            $type = $dependency->getType();
            
            if ($type && !$type->isBuiltin()) {
                $results[] = $this->make($type->getName());
            } else {
                $results[] = null;
            }
        }
        
        return $results;
    }
    
    public function basePath($path = '')
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
    
    public function configPath()
    {
        return $this->basePath('config');
    }
    
    public function publicPath()
    {
        return $this->basePath('public');
    }
    
    protected static $instance;
    
    public static function setInstance($app)
    {
        static::$instance = $app;
    }
    
    public static function getInstance()
    {
        return static::$instance;
    }

    /**
     * Handle an incoming HTTP request.
     * This method bootstraps route files and dispatches the request via Router.
     */
    public function handleRequest(\Aksa\Http\Request $request)
    {
        $router = new \Aksa\Routing\Router();

        // register middleware aliases/groups stored via withMiddleware
        foreach ($this->middlewareAliases as $alias => $class) {
            $router->registerMiddlewareAlias($alias, $class);
        }

        foreach ($this->middlewareGroups as $name => $items) {
            $router->registerMiddlewareGroup($name, $items);
        }

        // ensure default groups exist (web/api) so route group names aren't treated as classes
        if (!isset($this->middlewareGroups['web'])) {
            $router->registerMiddlewareGroup('web', ['session', 'csrf', 'cors']);
        }
        if (!isset($this->middlewareGroups['api'])) {
            $router->registerMiddlewareGroup('api', ['throttle', 'cors']);
        }

        // default aliases if none provided - prefer internal middleware, fall back to app-provided shims
        if (!isset($this->middlewareAliases['cors'])) {
            if (class_exists('\Aksa\\Http\\Middleware\\CorsMiddleware')) {
                $router->registerMiddlewareAlias('cors', \Aksa\Http\Middleware\CorsMiddleware::class);
            } elseif (class_exists('\App\\Middleware\\CorsMiddleware')) {
                $router->registerMiddlewareAlias('cors', \App\Middleware\CorsMiddleware::class);
            } elseif (class_exists('\App\\Http\\Middleware\\CorsMiddleware')) {
                $router->registerMiddlewareAlias('cors', \App\Http\Middleware\CorsMiddleware::class);
            }
        }

        if (!isset($this->middlewareAliases['auth'])) {
            if (class_exists('\Aksa\\Http\\Middleware\\Authenticate')) {
                $router->registerMiddlewareAlias('auth', \Aksa\Http\Middleware\Authenticate::class);
            } elseif (class_exists('\App\\Http\\Middleware\\Authenticate')) {
                $router->registerMiddlewareAlias('auth', \App\Http\Middleware\Authenticate::class);
            } elseif (class_exists('\App\\Middleware\\AuthMiddleware')) {
                $router->registerMiddlewareAlias('auth', \App\Middleware\AuthMiddleware::class);
            }
        }

        // register new middleware aliases for web/api behavior (prefer internal)
        if (!isset($this->middlewareAliases['session'])) {
            if (class_exists('\Aksa\\Http\\Middleware\\StartSession')) {
                $router->registerMiddlewareAlias('session', \Aksa\Http\Middleware\StartSession::class);
            } elseif (class_exists('\App\\Http\\Middleware\\StartSession')) {
                $router->registerMiddlewareAlias('session', \App\Http\Middleware\StartSession::class);
            }
        }

        if (!isset($this->middlewareAliases['csrf'])) {
            if (class_exists('\Aksa\\Http\\Middleware\\VerifyCsrfToken')) {
                $router->registerMiddlewareAlias('csrf', \Aksa\Http\Middleware\VerifyCsrfToken::class);
            } elseif (class_exists('\App\\Http\\Middleware\\VerifyCsrfToken')) {
                $router->registerMiddlewareAlias('csrf', \App\Http\Middleware\VerifyCsrfToken::class);
            }
        }

        if (!isset($this->middlewareAliases['throttle'])) {
            if (class_exists('\Aksa\\Http\\Middleware\\ThrottleRequests')) {
                $router->registerMiddlewareAlias('throttle', \Aksa\Http\Middleware\ThrottleRequests::class);
            } elseif (class_exists('\App\\Http\\Middleware\\ThrottleRequests')) {
                $router->registerMiddlewareAlias('throttle', \App\Http\Middleware\ThrottleRequests::class);
            }
        }

        // set router facade
        \Aksa\Routing\Route::setRouter($router);

        $base = $this->basePath();

        // load routing files from configuration or fallback to defaults
        if (!empty($this->routeConfig['web']) && file_exists($this->routeConfig['web'])) {
            $router->group(['middleware' => 'web'], function($r) {
                require $this->routeConfig['web'];
            });
        } elseif (file_exists($base . '/routes/web.php')) {
            $router->group(['middleware' => 'web'], function($r) use ($base) {
                require $base . '/routes/web.php';
            });
        }

        if (!empty($this->routeConfig['commands']) && file_exists($this->routeConfig['commands'])) {
            require $this->routeConfig['commands'];
        } elseif (file_exists($base . '/routes/console.php')) {
            require $base . '/routes/console.php';
        }

        if (!empty($this->routeConfig['api']) && file_exists($this->routeConfig['api'])) {
            $router->group(['prefix' => 'api', 'middleware' => 'api'], function($r) {
                require $this->routeConfig['api'];
            });
        } elseif (file_exists($base . '/routes/api.php')) {
            $router->group(['prefix' => 'api', 'middleware' => 'api'], function($r) use ($base) {
                require $base . '/routes/api.php';
            });
        }

        // optional plain routes
        if (!empty($this->routeConfig['none']) && file_exists($this->routeConfig['none'])) {
            require $this->routeConfig['none'];
        } elseif (file_exists($base . '/routes/none.php')) {
            require $base . '/routes/none.php';
        }

        try {
            $response = $router->dispatch($request);

            if ($response instanceof \Aksa\Http\Response) {
                $response->send();
            } else {
                echo $response;
            }

            return $response;
        } catch (\Throwable $e) {
            $handlerClass = $this->exceptionHandlerClass ?? '\\App\\Exceptions\\Handler';
            if (class_exists($handlerClass)) {
                $handler = new $handlerClass();
                $handler->report($e);
                $result = $handler->render($request, $e);

                if ($result instanceof \Aksa\Http\Response) {
                    $result->send();
                } else {
                    echo $result;
                }

                return $result;
            }

            // fallback
            http_response_code(500);
            echo "Server Error";
            return null;
        }
    }
}
