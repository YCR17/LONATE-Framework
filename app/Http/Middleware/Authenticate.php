<?php

namespace App\Http\Middleware;

use MiniLaravel\Http\Request;

class Authenticate
{
    public function handle(Request $request, $next)
    {
        // TODO: implement middleware logic
        return $next($request);
    }
}
