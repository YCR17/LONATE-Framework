<?php

require __DIR__ . '/vendor/autoload.php';

// 1. Test Artisan Command
echo "\n--- Testing Artisan Command ---\n";
// We execute the artisan script via shell to prove mostly-real world usage, 
// OR we can include it. Including is safer for this environment.
// But artisan has exit(), so we can't include it directly without wrapping.
// We'll just instantiate Kernel manually like artisan does.

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Lonate\Core\Console\Kernel::class);
$kernel->handle(['artisan', 'lonate:legitimize']);


// 2. Test Expanded ORM (Create/Save)
echo "\n--- Testing ORM CRUD ---\n";
// Debug Config
$dbConfig = config('database');
if (!isset($dbConfig['default'])) {
    echo "[ERROR] Database config missing 'default' key. Dump:\n";
    print_r($dbConfig);
}

// Define temporary model
class Farm extends \Lonate\Core\Database\Model {
    protected ?string $table = 'farms';
    protected $fillable = ['location', 'size'];
}

$farm = new Farm(['location' => 'Sumatera', 'size' => 1000]);
$farm->save();
echo "Farm saved. Exists: " . ($farm->exists ? 'Yes' : 'No') . "\n";

// 3. Test View Helper
echo "\n--- Testing View Helper ---\n";
try {
    $view = view('dashboard', ['region' => 'Aceh', 'status' => 'Legitimate']);
    // Capture output of the response object (it echoes)
    ob_start();
    $view->send();
    $content = ob_get_clean(); 
    echo substr(strip_tags($content), 0, 100) . "...\n";
} catch (\Exception $e) {
    echo "View Error: " . $e->getMessage();
}

// 4. Test Config Helper
echo "\n--- Testing Helpers ---\n";
echo "Config DB Default: " . config('database.default') . "\n";
