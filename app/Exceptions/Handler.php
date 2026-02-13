<?php

namespace App\Exceptions;

use Lonate\Core\Http\Request;
use Lonate\Core\Http\Response;

class Handler
{
    public function report(\Throwable $e): void
    {
        error_log((string) $e);
    }

    public function render(Request $request, \Throwable $e): Response
    {
        $debug = config('app.debug', false);

        if ($request->expectsJson()) {
            return Response::json([
                'error' => $e->getMessage(),
            ], 500);
        }

        if ($debug) {
            $content = "<h1>Whoops!</h1>";
            $content .= "<p>" . htmlentities($e->getMessage()) . "</p>";
            $content .= "<pre>" . htmlentities($e->getTraceAsString()) . "</pre>";
        } else {
            $content = "<h1>500 Server Error</h1>";
            $content .= "<p>Something went wrong.</p>";
        }

        return new Response($content, 500);
    }

    public function renderForConsole(\Throwable $e): void
    {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
