<?php

namespace MiniLaravel\Http\Middleware;

use MiniLaravel\Http\Request;
use MiniLaravel\Http\Response;

class VerifyCsrfToken
{
    protected $except = [];

    public function handle(Request $request, $next)
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        $method = $request->method();
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $token = $request->input('_token') ?? $request->header('X-CSRF-TOKEN');

            if (!$token || !isset($_SESSION['_csrf_token']) || $token !== $_SESSION['_csrf_token']) {
                return Response::plain('CSRF token mismatch.', 419);
            }
        }

        return $next($request);
    }
}
