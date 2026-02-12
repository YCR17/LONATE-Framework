<?php

namespace App\Http\Middleware;

use MiniLaravel\Http\Request;

class ThrottleRequests
{
    protected $maxAttempts = 60;
    protected $decaySeconds = 60;

    public function handle(Request $request, $next)
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $ip = $request->ip();
        $key = "throttle." . $ip;

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['hits' => 0, 'started_at' => time()];
        }

        $data = &$_SESSION[$key];

        if (time() - $data['started_at'] > $this->decaySeconds) {
            $data['hits'] = 0;
            $data['started_at'] = time();
        }

        $data['hits']++;

        if ($data['hits'] > $this->maxAttempts) {
            return response('Too Many Requests', 429)->header('Retry-After', $this->decaySeconds);
        }

        return $next($request);
    }
}
