<?php

use MiniLaravel\View\ViewEngine;
use MiniLaravel\Http\Request;

// Test 1: sections / yield
$engine = new ViewEngine();
$html = $engine->render('welcome', []);
if (strpos($html, '<h1>🚀 MiniLaravel</h1>') === false) {
    echo "welcome content not found\n";
    return false;
}

// Test 2: csrf_field and csrf_token
if (session_status() === PHP_SESSION_NONE) session_start();
$token = csrf_token();
if (empty($token) || !isset($_SESSION['_csrf_token'])) {
    echo "csrf_token not set\n";
    return false;
}

$field = csrf_field();
if (strpos($field, '_token') === false || strpos($field, $token) === false) {
    echo "csrf_field incorrect\n";
    return false;
}

// Test 3: VerifyCsrfToken middleware allows valid token
// simulate POST with correct token
$_POST['_token'] = $token;
$req = new Request();
$mw = new \App\Http\Middleware\VerifyCsrfToken();
$called = false;
$next = function($r) use (&$called) { $called = true; return 'ok'; };
$response = $mw->handle($req, $next);
if ($called !== true) {
    echo "VerifyCsrfToken did not call next\n";
    return false;
}

return true;
