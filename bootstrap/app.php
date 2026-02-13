<?php

use Lonate\Core\Foundation\Application;
use Lonate\Core\Http\Kernel;
use Lonate\Core\Http\Router;

require __DIR__ . '/../vendor/autoload.php';

// 1. Load Environment Variables
$dotenv = \Lonate\Core\Support\DotEnv::create(dirname(__DIR__) . '/.env');
$dotenv->load();

// 2. Create Application
$app = new Application(dirname(__DIR__));

// 3. Register Configured Providers (Core + App)
// This replaces manual singleton registration
$app->registerConfiguredProviders();

// 4. Register Static Facades (Legacy-ish but useful)
\Lonate\Core\Support\Facade::setFacadeApplication($app);

// 5. Boot Providers
$app->boot();

// 6. Exposed to Global (for helpers)
$GLOBALS['app'] = $app;

// 7. Return App
return $app;
