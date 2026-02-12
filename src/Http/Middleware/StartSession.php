<?php

namespace MiniLaravel\Http\Middleware;

use MiniLaravel\Http\Request;

class StartSession
{
    public function handle(Request $request, $next)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return $next($request);
    }
}
