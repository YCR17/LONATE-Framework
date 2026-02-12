<?php

namespace MiniLaravel\Http\Middleware;

use MiniLaravel\Http\Request;
use MiniLaravel\Http\Response;

class AuthMiddleware
{
    public function handle(Request $request, $next)
    {
        $token = $request->header('Authorization');

        if (!$token) {
            return Response::plain('Unauthorized', 401);
        }

        return $next($request);
    }
}
