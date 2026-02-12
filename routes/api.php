<?php

use MiniLaravel\Routing\Route;
use MiniLaravel\Http\Request;

Route::get('/api/health', function(Request $request) {
    return json(['status' => 'ok']);
});

