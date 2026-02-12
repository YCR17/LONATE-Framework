<?php

namespace App\Http\Middleware;

use Aksa\Http\Request;

class CorsMiddleware
{
    public function handle(Request $request, $next)
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

        if ($request->method() === 'OPTIONS') {
            http_response_code(200);
            exit();
        }

        return $next($request);
    }
}
