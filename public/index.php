<?php

use Lonate\Core\Http\Kernel;
use Lonate\Core\Http\Request;

// 1. Bootstrap
$app = require __DIR__ . '/../bootstrap/app.php';

// 2. Capture Request
$request = Request::capture();

// 3. Handle Request
$kernel = $app->make(Kernel::class);
$response = $kernel->handle($request);

// 4. Send Response
$response->send();
