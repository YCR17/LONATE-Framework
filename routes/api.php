<?php

/**
 * API Routes
 * 
 * Define your application's API routes here.
 * The $router variable is available and is an instance of Router.
 */

$router->get('api/status', function ($request) {
    return \Lonate\Core\Http\Response::json([
        'status' => 'ok',
        'framework' => 'LONATE',
        'version' => \Lonate\Core\Foundation\Application::VERSION,
    ]);
});