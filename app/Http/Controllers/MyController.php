<?php

namespace App\Http\Controllers;

use Lonate\Core\Http\Request;
use Lonate\Core\Http\Response;
use Lonate\Core\Trade\WowoEngine;

class MyController
{
    public function index(Request $request): Response
    {
        $dbPath = __DIR__ . '/../../storage/wowo.db';
        var_dump($engine = new WowoEngine($dbPath));
        // return response(['test' => 'test']);
        return Response::json(['message' => 'Hello from Controller']);
    }
}