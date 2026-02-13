<?php

/**
 * Web Routes
 * 
 * Define your application's web routes here.
 * The $router variable is available and is an instance of Router.
 */

$router->get('/', function ($request) {
    return view('welcome');
});

$router->get('/test', [\App\Http\Controllers\MyController::class, 'index']);