<?php

namespace App\Http\Middleware;

use MiniLaravel\Http\Request;

class AuthMiddleware
{
    public function handle(Request $request, $next)
    {
        $token = $request->header('Authorization');

        if (!$token) {
            return response('Unauthorized', 401);
        }

        return $next($request);
    }
}
