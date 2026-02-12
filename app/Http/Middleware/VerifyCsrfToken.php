<?php

namespace App\Http\Middleware;

use Aksa\Http\Request;

class VerifyCsrfToken
{
    protected $except = [
        // URIs that should be excluded from CSRF verification
    ];

    public function handle(Request $request, $next)
    {
        // ensure session token exists for GET pages that render forms
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
                return response('CSRF token mismatch.', 419);
            }
        }

        return $next($request);
    }
}
