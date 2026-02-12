<?php

namespace App\Http\Middleware;

use Aksa\Http\Request;

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
