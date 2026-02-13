<?php

require __DIR__ . '/vendor/autoload.php';

echo "--- Testing Modular Architecture ---\n";

// 1. Bootstrapping via new bootstrap file
try {
    $app = require __DIR__ . '/bootstrap/app.php';
    echo "[OK] Bootstrap via Service Providers successful.\n";
} catch (\Exception $e) {
    die("[FAIL] Bootstrap failed: " . $e->getMessage() . "\n");
}

// 2. Test DotEnv
$envName = env('APP_NAME');
if ($envName === 'LONATE_DEV') {
    echo "[OK] .env loaded successfully (APP_NAME=$envName).\n";
} else {
    echo "[FAIL] .env loading failed (APP_NAME=$envName).\n";
}

// 3. Test Service Container via Provider
if ($app->make(\Lonate\Core\Http\Router::class)) {
    echo "[OK] Router resolved from CoreServiceProvider.\n";
} else {
    echo "[FAIL] Router did not resolve.\n";
}

// 4. Test Config
$providers = config('app.providers');
if (is_array($providers) && count($providers) > 0) {
    echo "[OK] Config loaded providers list.\n";
} else {
    echo "[FAIL] Config app.providers missing.\n";
}

// 5. Test Exception Handler (Manual Mock)
$handler = $app->make(\Lonate\Core\Exceptions\Handler::class);
if ($handler) {
    echo "[OK] Exception Handler resolved.\n";
}
