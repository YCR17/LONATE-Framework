<?php

use Aksa\Routing\Route;
use Aksa\Http\Request;

Route::get('/api/health', function(Request $request) {
    return json(['status' => 'ok']);
});

