<?php

use MiniLaravel\Routing\Route;
use MiniLaravel\Http\Request;

// Simple route with closure
Route::get('/', function(Request $request) {
    return view('welcome');
});

// Route with controller (string "Controller@method")
Route::get('/apis', 'UserController@sendToExternal');
