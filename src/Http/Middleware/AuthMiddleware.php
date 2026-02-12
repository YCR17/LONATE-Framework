<?php

namespace Aksa\Http\Middleware;

use Aksa\Http\Request;
use Aksa\Http\Response;

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
