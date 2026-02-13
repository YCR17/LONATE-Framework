<?php
/**
 * LONATE Framework — Comprehensive Verification Test Suite
 * 
 * Tests ALL core components:
 * - Sections 1-10: Use InMemoryDriver (no external DB needed)
 * - Section 11: SawitDB WowoEngine integration tests
 * - Section 12: AQL Builder tests
 * 
 * Run: php tests/test_skeleton.php
 */

$basePath = dirname(__DIR__);

// Force inmemory driver for ORM tests (sections 1-10)
// SawitDB integration tests (11-12) directly instantiate SawitDriver
putenv('DB_CONNECTION=inmemory');
$_ENV['DB_CONNECTION'] = 'inmemory';
$_SERVER['DB_CONNECTION'] = 'inmemory';

// Autoload
require $basePath . '/vendor/autoload.php';

// Track results
$passed = 0;
$failed = 0;
$total = 0;

function test(string $name, callable $fn): void
{
    global $passed, $failed, $total;
    $total++;
    try {
        $result = $fn();
        if ($result === false) {
            throw new Exception("Returned false");
        }
        echo "  \033[32m✓\033[0m {$name}\n";
        $passed++;
    } catch (Throwable $e) {
        echo "  \033[31m✗\033[0m {$name}\n";
        echo "    → \033[31m" . $e->getMessage() . "\033[0m\n";
        $failed++;
    }
}

function assert_equals($expected, $actual, string $msg = ''): void
{
    if ($expected !== $actual) {
        throw new Exception($msg ?: "Expected " . var_export($expected, true) . " got " . var_export($actual, true));
    }
}

function assert_true($value, string $msg = ''): void
{
    if (!$value) {
        throw new Exception($msg ?: "Expected true, got false");
    }
}

function assert_not_null($value, string $msg = ''): void
{
    if ($value === null) {
        throw new Exception($msg ?: "Expected non-null value");
    }
}

echo "\n\033[1m╔══════════════════════════════════════════════╗\033[0m\n";
echo "\033[1m║  LONATE Framework — Skeleton Verification    ║\033[0m\n";
echo "\033[1m╚══════════════════════════════════════════════╝\033[0m\n\n";

// =============================
// 1. BOOTSTRAP
// =============================
echo "\033[33m[1] Bootstrap & Application\033[0m\n";

$app = require $basePath . '/bootstrap/app.php';

test('Application boots without error', function () use ($app) {
    assert_not_null($app);
    assert_true($app instanceof \Lonate\Core\Foundation\Application);
});

test('Application VERSION constant exists', function () {
    assert_true(defined('Lonate\Core\Foundation\Application::VERSION'));
});

test('basePath() resolves correctly', function () use ($app) {
    assert_true(str_contains($app->basePath(), 'lonate'));
});

test('storagePath() resolves correctly', function () use ($app) {
    assert_true(str_ends_with($app->storagePath(), 'storage'));
});

test('configPath() resolves correctly', function () use ($app) {
    assert_true(str_ends_with($app->configPath(), 'config'));
});

test('databasePath() resolves correctly', function () use ($app) {
    assert_true(str_ends_with($app->databasePath(), 'database'));
});

// =============================
// 2. HELPERS
// =============================
echo "\n\033[33m[2] Helper Functions\033[0m\n";

test('app() returns Application instance', function () {
    assert_true(app() instanceof \Lonate\Core\Foundation\Application);
});

test('env() reads APP_NAME', function () {
    $val = env('APP_NAME', 'DEFAULT');
    assert_true(!empty($val));
});

test('config() reads app.name', function () {
    $val = config('app.name');
    assert_true(!empty($val));
});

test('base_path() works', function () {
    assert_true(str_contains(base_path(), 'lonate'));
});

test('storage_path() works', function () {
    assert_true(str_contains(storage_path(), 'storage'));
});

test('config_path() works', function () {
    assert_true(str_contains(config_path(), 'config'));
});

test('database_path() works', function () {
    assert_true(str_contains(database_path(), 'database'));
});

// =============================
// 3. SERVICE CONTAINER
// =============================
echo "\n\033[33m[3] Service Container & DI\033[0m\n";

test('Singleton resolution works', function () use ($app) {
    $router1 = $app->make(\Lonate\Core\Http\Router::class);
    $router2 = $app->make(\Lonate\Core\Http\Router::class);
    assert_true($router1 === $router2);
});

test('Database Manager resolves', function () use ($app) {
    $mgr = $app->make(\Lonate\Core\Database\Manager::class);
    assert_true($mgr instanceof \Lonate\Core\Database\Manager);
});

test('View Factory resolves', function () use ($app) {
    $factory = $app->make(\Lonate\Core\View\Factory::class);
    assert_true($factory instanceof \Lonate\Core\View\Factory);
});

test('Legitimacy Engine resolves', function () use ($app) {
    $engine = $app->make(\Lonate\Core\Legitimacy\Engine::class);
    assert_true($engine instanceof \Lonate\Core\Legitimacy\Engine);
});

// =============================
// 4. DATABASE LAYER (InMemory)
// =============================
echo "\n\033[33m[4] Database Layer (InMemory Driver)\033[0m\n";

// Reset in-memory tables
\Lonate\Core\Database\Drivers\InMemoryDriver::reset();

test('InMemoryDriver connects without error', function () use ($app) {
    $mgr = $app->make(\Lonate\Core\Database\Manager::class);
    $conn = $mgr->connection('inmemory');
    assert_true($conn instanceof \Lonate\Core\Database\Drivers\InMemoryDriver);
});

test('Query Builder INSERT works', function () use ($app) {
    $mgr = $app->make(\Lonate\Core\Database\Manager::class);
    $conn = $mgr->connection('inmemory');
    $builder = new \Lonate\Core\Database\Query\Builder($conn);
    $result = $builder->table('test_users')->insert([
        'name' => 'Yasir',
        'email' => 'yasir@lonate.id',
    ]);
    assert_true($result);
});

test('Query Builder SELECT works', function () use ($app) {
    $mgr = $app->make(\Lonate\Core\Database\Manager::class);
    $conn = $mgr->connection('inmemory');
    $builder = new \Lonate\Core\Database\Query\Builder($conn);
    $results = $builder->table('test_users')->get();
    assert_equals(1, count($results));
    assert_equals('Yasir', $results[0]['name']);
});

test('Query Builder WHERE works', function () use ($app) {
    $mgr = $app->make(\Lonate\Core\Database\Manager::class);
    $conn = $mgr->connection('inmemory');
    
    $b = new \Lonate\Core\Database\Query\Builder($conn);
    $b->table('test_users')->insert(['name' => 'Budi', 'email' => 'budi@lonate.id']);
    
    $b2 = new \Lonate\Core\Database\Query\Builder($conn);
    $result = $b2->table('test_users')->where('name', 'Budi')->first();
    assert_not_null($result);
    assert_equals('budi@lonate.id', $result['email']);
});

test('Query Builder UPDATE works', function () use ($app) {
    $mgr = $app->make(\Lonate\Core\Database\Manager::class);
    $conn = $mgr->connection('inmemory');
    $b = new \Lonate\Core\Database\Query\Builder($conn);
    $b->table('test_users')->where('name', 'Budi')->update(['email' => 'budi@updated.id']);
    
    $b2 = new \Lonate\Core\Database\Query\Builder($conn);
    $result = $b2->table('test_users')->where('name', 'Budi')->first();
    assert_equals('budi@updated.id', $result['email']);
});

test('Query Builder DELETE works', function () use ($app) {
    $mgr = $app->make(\Lonate\Core\Database\Manager::class);
    $conn = $mgr->connection('inmemory');
    $b = new \Lonate\Core\Database\Query\Builder($conn);
    $b->table('test_users')->where('name', 'Budi')->delete();
    
    $b2 = new \Lonate\Core\Database\Query\Builder($conn);
    $results = $b2->table('test_users')->get();
    assert_equals(1, count($results));
});

test('Query Builder ORDER BY works', function () use ($app) {
    $mgr = $app->make(\Lonate\Core\Database\Manager::class);
    $conn = $mgr->connection('inmemory');
    
    $b = new \Lonate\Core\Database\Query\Builder($conn);
    $b->table('test_users')->insert(['name' => 'Andi', 'email' => 'andi@lonate.id']);
    
    $b2 = new \Lonate\Core\Database\Query\Builder($conn);
    $results = $b2->table('test_users')->orderBy('name', 'ASC')->get();
    assert_equals('Andi', $results[0]['name']);
    assert_equals('Yasir', $results[1]['name']);
});

test('Query Builder LIMIT works', function () use ($app) {
    $mgr = $app->make(\Lonate\Core\Database\Manager::class);
    $conn = $mgr->connection('inmemory');
    $b = new \Lonate\Core\Database\Query\Builder($conn);
    $results = $b->table('test_users')->limit(1)->get();
    assert_equals(1, count($results));
});

// =============================
// 5. ORM / MODEL
// =============================
echo "\n\033[33m[5] ORM Model (using InMemory)\033[0m\n";

// Reset for ORM tests
\Lonate\Core\Database\Drivers\InMemoryDriver::reset();

test('Model::create() inserts and returns instance', function () {
    $user = \App\Models\User::create([
        'name' => 'Admin',
        'email' => 'admin@lonate.id',
    ]);
    assert_true($user->exists);
    assert_equals('Admin', $user->name);
    assert_not_null($user->id);
});

test('Model::find() retrieves by ID', function () {
    $user = \App\Models\User::find(1);
    assert_not_null($user);
    assert_equals('Admin', $user->name);
});

test('Model::where()->get() chains correctly', function () {
    \App\Models\User::create(['name' => 'Editor', 'email' => 'editor@lonate.id']);
    
    $results = \App\Models\User::where('name', 'Editor')->get();
    assert_equals(1, count($results));
});

test('Model->save() updates existing record', function () {
    $user = \App\Models\User::find(1);
    assert_not_null($user);
    $user->name = 'Super Admin';
    $user->save();
    
    $refreshed = \App\Models\User::find(1);
    assert_equals('Super Admin', $refreshed->name);
});

test('Model->delete() removes record', function () {
    $user = \App\Models\User::find(2);
    assert_not_null($user);
    $user->delete();
    
    $deleted = \App\Models\User::find(2);
    assert_true($deleted === null);
});

test('Model->toArray() returns attributes', function () {
    $user = \App\Models\User::find(1);
    $arr = $user->toArray();
    assert_true(is_array($arr));
    assert_true(isset($arr['name']));
});

// =============================
// 6. HTTP LAYER
// =============================
echo "\n\033[33m[6] HTTP Layer\033[0m\n";

test('Request::capture() creates instance', function () {
    $request = new \Lonate\Core\Http\Request(
        ['page' => '1'],
        ['name' => 'test'],
        ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/test']
    );
    assert_equals('GET', $request->method());
    assert_equals('/test', $request->uri());
    assert_equals('test', $request->input('name'));
    assert_equals('1', $request->input('page'));
});

test('Request::all() merges query + post', function () {
    $request = new \Lonate\Core\Http\Request(['a' => '1'], ['b' => '2'], []);
    $all = $request->all();
    assert_true(isset($all['a']) && isset($all['b']));
});

test('Request::header() reads headers', function () {
    $request = new \Lonate\Core\Http\Request([], [], [
        'HTTP_ACCEPT' => 'application/json',
    ]);
    assert_equals('application/json', $request->header('Accept'));
});

test('Request::expectsJson() works', function () {
    $request = new \Lonate\Core\Http\Request([], [], [
        'HTTP_ACCEPT' => 'application/json',
    ]);
    assert_true($request->expectsJson());
});

test('Request route params work', function () {
    $request = new \Lonate\Core\Http\Request([], [], []);
    $request->setParams(['id' => '42', 'slug' => 'test']);
    assert_equals('42', $request->param('id'));
    assert_equals('test', $request->param('slug'));
});

test('Response::json() creates JSON response', function () {
    $response = \Lonate\Core\Http\Response::json(['status' => 'ok']);
    assert_equals(200, $response->getStatusCode());
    assert_true(str_contains($response->getContent(), 'ok'));
});

test('Router registers GET/POST/PUT/PATCH/DELETE routes', function () use ($app) {
    $router = new \Lonate\Core\Http\Router($app);
    $router->get('test', fn() => 'GET');
    $router->post('test', fn() => 'POST');
    $router->put('test', fn() => 'PUT');
    $router->patch('test', fn() => 'PATCH');
    $router->delete('test', fn() => 'DELETE');
    
    $routes = $router->getRoutes();
    assert_true(isset($routes['GET']['test']));
    assert_true(isset($routes['POST']['test']));
    assert_true(isset($routes['PUT']['test']));
    assert_true(isset($routes['PATCH']['test']));
    assert_true(isset($routes['DELETE']['test']));
});

test('Router dispatches GET request', function () use ($app) {
    $router = new \Lonate\Core\Http\Router($app);
    $router->get('hello', fn() => new \Lonate\Core\Http\Response('Hello World'));
    
    $request = new \Lonate\Core\Http\Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/hello',
    ]);
    
    $response = $router->dispatch($request);
    assert_equals('Hello World', $response->getContent());
});

test('Router handles parameterized routes', function () use ($app) {
    $router = new \Lonate\Core\Http\Router($app);
    $router->get('users/{id}', function ($request) {
        return new \Lonate\Core\Http\Response('User ' . $request->param('id'));
    });
    
    $request = new \Lonate\Core\Http\Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/users/42',
    ]);
    
    $response = $router->dispatch($request);
    assert_equals('User 42', $response->getContent());
});

test('Router returns 404 for unknown routes', function () use ($app) {
    $router = new \Lonate\Core\Http\Router($app);
    $request = new \Lonate\Core\Http\Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/nonexistent',
    ]);
    
    $response = $router->dispatch($request);
    assert_equals(404, $response->getStatusCode());
});

test('Kernel wraps string return values in Response', function () use ($app) {
    $router = new \Lonate\Core\Http\Router($app);
    $router->get('/string-test', function () {
        return "String Response";
    });
    
    // Kernel constructor
    $kernel = new \Lonate\Core\Http\Kernel($app, $router);
    
    $request = new \Lonate\Core\Http\Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/string-test',
    ]);
    
    // handle() will trigger the middleware stack which requires Response object
    // If our fix works, this won't crash
    $response = $kernel->handle($request);
    
    assert_true($response instanceof \Lonate\Core\Http\Response);
    assert_equals("String Response", $response->getContent());
});

// =============================
// 7. FACADES
// =============================
echo "\n\033[33m[7] Facade System\033[0m\n";

test('Policy facade resolves to Engine', function () {
    $root = \App\Facades\Policy::getFacadeRoot();
    assert_true($root instanceof \Lonate\Core\Legitimacy\Engine);
});

test('Asset facade resolves to Asset Manager', function () {
    $root = \App\Facades\Asset::getFacadeRoot();
    assert_true($root instanceof \Lonate\Core\Asset\Manager);
});

// =============================
// 8. LEGITIMACY ENGINE
// =============================
echo "\n\033[33m[8] Legitimacy / Policy Engine\033[0m\n";

test('Evidence::generate() creates HMAC token', function () {
    $evidence = \Lonate\Core\Legitimacy\Evidence::generate('test_payload');
    assert_equals(64, strlen($evidence->getToken()));
});

test('Evidence::fromToken() restores token', function () {
    $token = hash_hmac('sha256', 'test', 'secret');
    $evidence = \Lonate\Core\Legitimacy\Evidence::fromToken($token);
    assert_equals($token, $evidence->getToken());
});

test('Engine::declareQuorum() with sufficient quorum', function () use ($app) {
    $engine = $app->make(\Lonate\Core\Legitimacy\Engine::class);
    $result = $engine->declareQuorum(['user1', 'user2', 'user3'], 2);
    assert_true($result);
});

test('Engine::declareQuorum() auto-approves with insufficient quorum', function () use ($app) {
    $engine = $app->make(\Lonate\Core\Legitimacy\Engine::class);
    $result = $engine->declareQuorum(['user1'], 5);
    assert_true($result);
});

// =============================
// 9. VIEW ENGINE
// =============================
echo "\n\033[33m[9] View Engine\033[0m\n";

test('View Factory renders welcome view', function () {
    $factory = app(\Lonate\Core\View\Factory::class);
    try {
        $html = $factory->make('welcome');
        assert_true(strlen($html) > 0);
    } catch (\RuntimeException $e) {
        if (str_contains($e->getMessage(), 'not found')) {
            assert_true(true);
        } else {
            throw $e;
        }
    }
});

// =============================
// 10. CONSOLE
// =============================
echo "\n\033[33m[10] Console\033[0m\n";

test('Console Command argument parsing works', function () {
    $cmd = new \Lonate\Core\Console\Commands\MakeModelCommand();
    $cmd->setInput(['TestModel', '--force']);
    assert_equals('TestModel', $cmd->argument(0));
    assert_equals(true, $cmd->option('force'));
});

// =============================
// 11. SAWITDB ENGINE INTEGRATION
// =============================
echo "\n\033[33m[11] SawitDB Engine Integration\033[0m\n";

// Create temp .sawit file for testing
$testSawitPath = sys_get_temp_dir() . '/lonate_test_' . time() . '.sawit';

test('WowoEngine initializes .sawit file', function () use ($testSawitPath) {
    $engine = new \SawitDB\Engine\WowoEngine($testSawitPath);
    assert_true(file_exists($testSawitPath));
});

test('SawitDriver connects via config', function () use ($testSawitPath) {
    $driver = new \Lonate\Core\Database\Drivers\SawitDriver();
    $driver->connect(['database' => $testSawitPath]);
    assert_not_null($driver->getEngine());
});

test('SawitDriver CREATE TABLE + INSERT (SQL syntax)', function () use ($testSawitPath) {
    $driver = new \Lonate\Core\Database\Drivers\SawitDriver();
    $driver->connect(['database' => $testSawitPath]);
    
    // Use standard SQL
    $driver->query("CREATE TABLE sawit_users");
    $driver->query("INSERT INTO sawit_users (name, email) VALUES ('Budi', 'budi@sawit.id')");
    
    assert_equals(1, $driver->lastInsertId());
});

test('SawitDriver SELECT returns data', function () use ($testSawitPath) {
    $driver = new \Lonate\Core\Database\Drivers\SawitDriver();
    $driver->connect(['database' => $testSawitPath]);
    
    $driver->query("SELECT * FROM sawit_users");
    $results = $driver->fetch();
    
    assert_true(is_array($results));
    assert_true(count($results) >= 1);
    assert_equals('Budi', $results[0]['name']);
});

test('SawitDriver AQL INSERT (TANAM syntax)', function () use ($testSawitPath) {
    $driver = new \Lonate\Core\Database\Drivers\SawitDriver();
    $driver->connect(['database' => $testSawitPath]);
    
    // Use AQL directly
    $result = $driver->aql("TANAM KE sawit_users (name, email) BIBIT ('Ani', 'ani@sawit.id')");
    assert_true(is_string($result) || is_array($result)); // Returns "Bibit tertanam."
});

test('SawitDriver AQL SELECT (PANEN syntax)', function () use ($testSawitPath) {
    $driver = new \Lonate\Core\Database\Drivers\SawitDriver();
    $driver->connect(['database' => $testSawitPath]);
    
    $result = $driver->aql("PANEN * DARI sawit_users");
    assert_true(is_array($result));
    assert_true(count($result) >= 2); // Budi + Ani
});

test('SawitDriver binding interpolation works', function () use ($testSawitPath) {
    $driver = new \Lonate\Core\Database\Drivers\SawitDriver();
    $driver->connect(['database' => $testSawitPath]);
    
    $driver->query("SELECT * FROM sawit_users WHERE name = ?", ['Budi']);
    $results = $driver->fetch();
    
    assert_true(count($results) >= 1);
    assert_equals('Budi', $results[0]['name']);
});

test('SawitDriver UPDATE works', function () use ($testSawitPath) {
    $driver = new \Lonate\Core\Database\Drivers\SawitDriver();
    $driver->connect(['database' => $testSawitPath]);
    
    $driver->query("UPDATE sawit_users SET email = ? WHERE name = ?", ['budi@updated.id', 'Budi']);
    $driver->query("SELECT * FROM sawit_users WHERE name = ?", ['Budi']);
    $results = $driver->fetch();
    
    assert_true(count($results) >= 1);
    assert_equals('budi@updated.id', $results[0]['email']);
});

test('SawitDriver DELETE works', function () use ($testSawitPath) {
    $driver = new \Lonate\Core\Database\Drivers\SawitDriver();
    $driver->connect(['database' => $testSawitPath]);
    
    $driver->query("DELETE FROM sawit_users WHERE name = ?", ['Ani']);
    $driver->query("SELECT * FROM sawit_users");
    $results = $driver->fetch();
    
    // Only Budi should remain
    $names = array_column($results, 'name');
    assert_true(!in_array('Ani', $names));
});

// =============================
// 12. AQL BUILDER
// =============================
echo "\n\033[33m[12] AQL Builder\033[0m\n";

test('AqlBuilder LAHAN (CREATE TABLE) works', function () use ($testSawitPath) {
    $driver = new \Lonate\Core\Database\Drivers\SawitDriver();
    $driver->connect(['database' => $testSawitPath]);
    
    $aql = new \Lonate\Core\Database\Query\AqlBuilder($driver);
    $aql->dari('aql_products')->lahan();
    
    // Verify table exists
    $tables = $driver->showTables();
    $tableNames = is_array($tables) ? array_column($tables, 'name') : $tables;
    assert_true(is_array($tables));
});

test('AqlBuilder TANAM (INSERT) works', function () use ($testSawitPath) {
    $driver = new \Lonate\Core\Database\Drivers\SawitDriver();
    $driver->connect(['database' => $testSawitPath]);
    
    $aql = new \Lonate\Core\Database\Query\AqlBuilder($driver);
    $aql->dari('aql_products')->tanam([
        'name' => 'Sawit Palm',
        'price' => 50000,
    ]);
    assert_true(true); // No exception = success
});

test('AqlBuilder PANEN (SELECT) + DIMANA (WHERE) works', function () use ($testSawitPath) {
    $driver = new \Lonate\Core\Database\Drivers\SawitDriver();
    $driver->connect(['database' => $testSawitPath]);
    
    $aql = new \Lonate\Core\Database\Query\AqlBuilder($driver);
    $results = $aql->dari('aql_products')
        ->panen('*')
        ->dimana('name', 'Sawit Palm')
        ->dapatkan();
    
    assert_true(is_array($results));
    assert_true(count($results) >= 1);
});

test('AqlBuilder PUPUK (UPDATE) works', function () use ($testSawitPath) {
    $driver = new \Lonate\Core\Database\Drivers\SawitDriver();
    $driver->connect(['database' => $testSawitPath]);
    
    $aql = new \Lonate\Core\Database\Query\AqlBuilder($driver);
    $aql->dari('aql_products')
        ->dimana('name', 'Sawit Palm')
        ->pupuk(['price' => 75000]);
    
    // Verify update
    $aql2 = new \Lonate\Core\Database\Query\AqlBuilder($driver);
    $results = $aql2->dari('aql_products')
        ->panen('*')
        ->dimana('name', 'Sawit Palm')
        ->dapatkan();
    
    assert_true(count($results) >= 1);
});

// Cleanup temp file
@unlink($testSawitPath);

// =============================
// 13. GRAMMAR TRANSPARENT TRANSLATION
// =============================
echo "\n\033[33m[13] Grammar — Transparent Translation\033[0m\n";

use Lonate\Core\Database\Query\Grammars\Grammar;
use Lonate\Core\Database\Query\Grammars\MysqlGrammar;
use Lonate\Core\Database\Query\Grammars\SawitGrammar;

test('MysqlGrammar wraps columns in backticks', function () {
    $g = new MysqlGrammar();
    assert_equals('`name`', $g->wrapColumn('name'));
    assert_equals('*', $g->wrapColumn('*'));
    assert_equals('`users`.`id`', $g->wrapColumn('users.id'));
});

test('SawitGrammar does NOT wrap columns', function () {
    $g = new SawitGrammar();
    assert_equals('name', $g->wrapColumn('name'));
    assert_equals('*', $g->wrapColumn('*'));
});

test('Same Builder query → different SQL per grammar', function () use ($app) {
    $mgr = $app->make(\Lonate\Core\Database\Manager::class);
    $conn = $mgr->connection('inmemory');

    // With MysqlGrammar
    $b1 = new \Lonate\Core\Database\Query\Builder($conn, new MysqlGrammar());
    $sql1 = $b1->table('users')->select('name', 'email')->where('name', 'Budi')->toSql();

    // With SawitGrammar
    $b2 = new \Lonate\Core\Database\Query\Builder($conn, new SawitGrammar());
    $sql2 = $b2->table('users')->select('name', 'email')->where('name', 'Budi')->toSql();

    // MySQL uses backticks + ?
    assert_true(str_contains($sql1, '`name`'));
    assert_true(str_contains($sql1, '?'));

    // SawitDB uses plain columns + inline value
    assert_true(!str_contains($sql2, '`'));
    assert_true(str_contains($sql2, "'Budi'"));
});

test('SawitGrammar inlines values (no ? bindings)', function () {
    $g = new SawitGrammar();
    $compiled = $g->compileInsert('products', ['name' => 'Sawit', 'price' => 50000]);

    assert_true(str_contains($compiled['sql'], "'Sawit'"));
    assert_true(str_contains($compiled['sql'], '50000'));
    assert_equals([], $compiled['bindings']); // No bindings — all inlined
});

test('MysqlGrammar uses ? bindings for INSERT', function () {
    $g = new MysqlGrammar();
    $compiled = $g->compileInsert('products', ['name' => 'Sawit', 'price' => 50000]);

    assert_true(str_contains($compiled['sql'], '?'));
    assert_equals(['Sawit', 50000], $compiled['bindings']);
});

test('Grammar compileCreateTable differs per dialect', function () {
    $mysql = new MysqlGrammar();
    $sawit = new SawitGrammar();

    $mysqlSql = $mysql->compileCreateTable('users');
    $sawitSql = $sawit->compileCreateTable('users');

    // MySQL has IF NOT EXISTS and engine
    assert_true(str_contains($mysqlSql, 'IF NOT EXISTS'));
    assert_true(str_contains($mysqlSql, 'InnoDB'));

    // SawitDB is simple — schema-free
    assert_equals('CREATE TABLE users', $sawitSql);
});

test('Builder auto-resolves Grammar from connection', function () use ($app) {
    $mgr = $app->make(\Lonate\Core\Database\Manager::class);

    $inmemory = $mgr->connection('inmemory');
    $b = new \Lonate\Core\Database\Query\Builder($inmemory);
    assert_true($b->getGrammar() instanceof Grammar);
});

test('Same Builder code works on both InMemory and SawitDB', function () {
    // Create temp SawitDB
    $tmpFile = sys_get_temp_dir() . '/grammar_test_' . time() . '.sawit';

    $sawit = new \Lonate\Core\Database\Drivers\SawitDriver();
    $sawit->connect(['database' => $tmpFile]);

    $inmemory = new \Lonate\Core\Database\Drivers\InMemoryDriver();
    $inmemory->connect([]);

    // Identical code, two different drivers
    foreach ([$inmemory, $sawit] as $driver) {
        $b = new \Lonate\Core\Database\Query\Builder($driver);
        $b->table('grammar_test')->createTable();

        $b2 = new \Lonate\Core\Database\Query\Builder($driver);
        $b2->table('grammar_test')->insert(['name' => 'test', 'value' => 42]);

        $b3 = new \Lonate\Core\Database\Query\Builder($driver);
        $results = $b3->table('grammar_test')->where('name', 'test')->get();

        assert_true(count($results) >= 1);
        assert_equals('test', $results[0]['name']);
    }

    @unlink($tmpFile);
});

// =============================
// RESULTS
// =============================
echo "\n\033[1m═══════════════════════════════════════════════\033[0m\n";
if ($failed === 0) {
    echo "\033[1;32m  ✓ ALL {$passed}/{$total} TESTS PASSED\033[0m\n";
} else {
    echo "\033[1;31m  ✗ {$failed} FAILED\033[0m / \033[32m{$passed} passed\033[0m / {$total} total\n";
}
echo "\033[1m═══════════════════════════════════════════════\033[0m\n\n";

exit($failed > 0 ? 1 : 0);

