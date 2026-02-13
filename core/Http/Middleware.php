<?php

namespace Lonate\Core\Http;

interface Middleware
{
    public function handle(Request $request, \Closure $next): Response;
}
