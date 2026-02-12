<?php

use MiniLaravel\Support\Application;
use MiniLaravel\Support\MiddlewareRegistrar;
use MiniLaravel\Support\ExceptionsConfigurator;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (MiddlewareRegistrar $middleware) {
        // Register default middleware aliases or groups here if you want
        // $middleware->alias('auth', App\Http\Middleware\Authenticate::class);
    })
    ->withExceptions(function (ExceptionsConfigurator $exceptions) {
        // Configure exception handler if desired
        $exceptions->handler(App\Exceptions\Handler::class);
    })->create();
