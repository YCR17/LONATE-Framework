<?php

namespace Lonate\Core\Exceptions;

use Lonate\Core\Http\Request;
use Lonate\Core\Http\Response;
use Throwable;

class Handler
{
    public function report(Throwable $e): void
    {
        // Check if logging is enabled, etc.
        error_log($e->getMessage());
    }

    public function render(Request $request, Throwable $e): Response
    {
        // If expectation is JSON
        // $request->expectsJson() ... 
        
        $debug = config('app.debug', false);
        
        if ($debug) {
            $content = "<h1>Whoops!</h1>";
            $content .= "<p>" . $e->getMessage() . "</p>";
            $content .= "<pre>" . $e->getTraceAsString() . "</pre>";
        } else {
            $content = "<h1>500 Server Error</h1>";
            $content .= "<p>Something went wrong.</p>";
        }
        
        return new Response($content, 500);
    }
}
