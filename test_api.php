<?php

require __DIR__ . '/vendor/autoload.php';

use Lonate\Core\Http\Request;
use Lonate\Core\Http\Kernel;

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Kernel::class);

echo "\n--- Testing API Endpoints ---\n";

// 1. Test Legitimize
echo "\n[POST] /api/legitimize/policy\n";
$req1 = new Request([], ['data' => 'illegal', 'screenshot' => 'proof.jpg'], ['REQUEST_URI' => '/api/legitimize/policy', 'REQUEST_METHOD' => 'POST']);
$res1 = $kernel->handle($req1);
ob_start(); $res1->send(); $out1 = ob_get_clean();
echo $out1 . "\n";

// 2. Test Sawit
echo "\n[POST] /api/sawit/unlicensed\n";
$req2 = new Request([], [], ['REQUEST_URI' => '/api/sawit/unlicensed', 'REQUEST_METHOD' => 'POST']);
try {
    $res2 = $kernel->handle($req2);
    $res2->send();
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

// 3. Test Kondusivitas
echo "\n[GET] /api/kondusivitas\n";
$req3 = new Request([], [], ['REQUEST_URI' => '/api/kondusivitas', 'REQUEST_METHOD' => 'GET']);
$res3 = $kernel->handle($req3);
ob_start(); $res3->send(); $out3 = ob_get_clean();
echo $out3 . "\n";
