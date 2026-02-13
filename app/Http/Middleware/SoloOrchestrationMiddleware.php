<?php

namespace App\Http\Middleware;

use Lonate\Core\Http\Middleware;
use Lonate\Core\Http\Request;
use Lonate\Core\Http\Response;
use Closure;

class SoloOrchestrationMiddleware implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Add "Cooling Down" logic
        // Only allow one request per user per... no we won't rate limit, we just warn.
        
        // 2. Process Next
        /** @var Response $response */
        $response = $next($request);
        
        // 3. Add Headers (The "Titip Pesan")
        $response->setHeader('X-Conductivity', 'Stable');
        $response->setHeader('X-Message', 'Jangan force push ke main, ya.');
        
        return $response;
    }
}
