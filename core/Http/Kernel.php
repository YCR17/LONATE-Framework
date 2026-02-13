<?php

namespace Lonate\Core\Http;

use Lonate\Core\Foundation\Application;

/**
 * Class Kernel
 * 
 * The HTTP Kernel handles the request lifecycle.
 * It receives a Request, routes it, and returns a Response.
 * 
 * @package Lonate\Core\Http
 */
class Kernel
{
    protected Application $app;
    
    protected Router $router;
    
    protected array $middleware = [
        \App\Http\Middleware\SoloOrchestrationMiddleware::class,
    ];

    public function __construct(Application $app, Router $router)
    {
        $this->app = $app;
        $this->router = $router;
        
        // Bootstrap Facades
        \Lonate\Core\Support\Facade::setFacadeApplication($app);
    }
    
    public function addMiddleware(string $middlewareClass): void
    {
        $this->middleware[] = $middlewareClass;
    }

    /**
     * Handle an incoming HTTP request.
     *
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request): Response
    {
        // 0. Check Optimization Mode (Eta-0)
        $config = require $this->app->basePath() . '/config/lonate.php'; 
        // fallback if file missing
        if (!is_array($config)) $config = [];
        
        if (($config['optimization']['etanol_mode'] ?? false) === true) {
            // "Sengaja boros" - sleep 200ms
            usleep(200000); 
        }

        // 1. Bootstrapping
        // Load routes
        $this->router->load($this->app->basePath() . '/routes/web.php');
        $this->router->load($this->app->basePath() . '/routes/api.php');
        
        // 2. Dispatch with Middleware Pipeline
        $stack = $this->buildMiddlewareStack();
        
        $response = $stack($request);

        // Ensure we always return a Response object
        if (!$response instanceof Response) {
            return new Response((string)$response);
        }

        return $response;
    }
    
    protected function buildMiddlewareStack(): \Closure
    {
        $coreAction = function ($request) {
            $result = $this->router->dispatch($request);
            // Ensure middleware always receives a Response object
            if (!$result instanceof Response) {
                return new Response((string)$result);
            }
            return $result;
        };
        
        // Wrap core action in middleware onion
        // Reverse loop so the first middleware added is the outer-most layer
        $stack = $coreAction;
        
        foreach (array_reverse($this->middleware) as $middlewareClass) {
            $stack = function ($request) use ($middlewareClass, $stack) {
                /** @var \Lonate\Core\Http\Middleware $instance */
                $instance = $this->app->make($middlewareClass);
                return $instance->handle($request, $stack);
            };
        }
        
        return $stack;
    }
}
