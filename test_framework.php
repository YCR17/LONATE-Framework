<?php

use Lonate\Core\Foundation\Application;
use Lonate\Core\Http\Kernel;
use Lonate\Core\Http\Request;
use Lonate\Core\Http\Router;
use App\Facades\Asset;
use App\Facades\Grant;

require __DIR__ . '/vendor/autoload.php';

function test_request($uri, $method = 'GET') {
    echo "\n\n--- Testing $method $uri ---\n";
    $start = microtime(true);
    
    $request = new Request([], [], ['REQUEST_URI' => $uri, 'REQUEST_METHOD' => $method]);

    $app = new Application(__DIR__);
    
    // Bindings
    $app->singleton(Router::class);
    $app->singleton(Kernel::class);
    $app->singleton(\Lonate\Core\Database\Manager::class);
    $app->singleton(\Lonate\Core\Legitimacy\Engine::class);
    $app->singleton(\Lonate\Core\Asset\Manager::class);
    $app->singleton(\Lonate\Core\Trade\Grant::class);
    
    // Globals
    global $app_instance;
    $app_instance = $app;
    $GLOBALS['app'] = $app; 

    $kernel = $app->make(Kernel::class);
    
    // Add Middleware for testing
    if ($uri === '/middleware-check') {
        $kernel->addMiddleware(\App\Http\Middleware\SoloOrchestrationMiddleware::class);
    }
    
    $response = $kernel->handle($request);
    
    $duration = (microtime(true) - $start) * 1000;
    
    // Output
    ob_start();
    $response->send();
    $content = ob_get_clean();
    $headers = headers_list(); // Won't capture virtual headers easily in CLI without xdebug, but logic ran.
    
    echo "Status: " . http_response_code() . "\n";
    echo "Duration: " . number_format($duration, 2) . "ms (Eta-0 Effect)\n";
    echo "Content: " . substr(strip_tags($content), 0, 200) . "...\n";
}

// 1. Test Facade Usage (Manual invocation to test static proxy)
// We need to setup the App statically first for Facades to work outside of Kernel handle loop in this script context
$app = new Application(__DIR__);
$app->singleton(\Lonate\Core\Asset\Manager::class);
$app->singleton(\Lonate\Core\Trade\Grant::class);
$app->singleton(\Lonate\Core\Database\Manager::class);
\Lonate\Core\Support\Facade::setFacadeApplication($app);

echo "\n--- Testing Asset Facade ---\n";
try {
    $result = Asset::swapCommitAccess('legacy_repo', ['from' => 'A', 'to' => 'B', 'method' => 'force_push']);
    print_r($result);
} catch (\Exception $e) {
    echo "Facade Error: " . $e->getMessage();
}

echo "\n--- Testing Grant Facade ---\n";
try {
    $grant = Grant::disburse(5000000)
        ->withSprintReviewPhoto('ValidProof')
        ->execute();
    print_r($grant);
} catch (\Exception $e) {
    echo "Grant Error: " . $e->getMessage();
}


// 2. Test Request Flow & Middleware
test_request('/middleware-check');

// 3. Test Normal Flow
test_request('/');
