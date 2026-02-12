<?php

namespace MiniLaravel\Http\Middleware;

use MiniLaravel\Http\Request;

class Authenticate
{
    public function handle(Request $request, $next)
    {
        // Default internal authenticate middleware: pass-through (app may override)
        return $next($request);
    }
}
