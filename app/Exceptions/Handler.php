<?php

namespace App\Exceptions;

use MiniLaravel\Http\Request;
use MiniLaravel\Http\Response;

class Handler
{
    public function report(\Throwable $e)
    {
        // Simple reporting to error log for now
        error_log((string) $e);
    }

    public function render(Request $request, \Throwable $e)
    {
        if ($request->ajax() || strpos($request->header('Accept') ?? '', 'application/json') !== false) {
            http_response_code(500);
            header('Content-Type: application/json');
            return json_encode(['error' => $e->getMessage()]);
        }

        http_response_code(500);
        return "<h1>Server Error</h1><p>" . htmlentities($e->getMessage()) . "</p>";
    }

    public function renderForConsole(\Throwable $e)
    {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
