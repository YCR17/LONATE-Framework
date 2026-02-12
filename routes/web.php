<?php

use Aksa\Routing\Route;
use Aksa\Http\Request;

// Simple route with closure
Route::get('/', function(Request $request) {
    return view('welcome');
});

// Route with controller (string "Controller@method")
Route::get('/apis', 'UserController@sendToExternal');
