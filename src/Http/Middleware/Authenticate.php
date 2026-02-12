<?php

namespace Aksa\Http\Middleware;

use Aksa\Http\Request;

class Authenticate
{
    public function handle(Request $request, $next)
    {
        // Default internal authenticate middleware: pass-through (app may override)
        return $next($request);
    }
}
