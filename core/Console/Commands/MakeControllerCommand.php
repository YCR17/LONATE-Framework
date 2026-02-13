<?php

namespace Lonate\Core\Console\Commands;

use Lonate\Core\Console\Command;

class MakeControllerCommand extends Command
{
    protected string $name = 'make:controller';
    protected string $description = 'Create a new controller class';

    public function handle(): int
    {
        $controllerName = $this->argument(0);

        if (!$controllerName) {
            $this->error('Please provide a controller name.');
            return 1;
        }

        $template = <<<PHP
<?php

namespace App\Http\Controllers;

use Lonate\Core\Http\Request;
use Lonate\Core\Http\Response;

class {$controllerName}
{
    public function index(Request \$request): Response
    {
        return Response::json(['message' => 'Hello from {$controllerName}']);
    }
}
PHP;

        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);
        $path = $basePath . "/app/Http/Controllers/{$controllerName}.php";

        if (file_exists($path) && !$this->option('force')) {
            $this->error("Controller [{$controllerName}] already exists! Use --force to overwrite.");
            return 1;
        }

        @mkdir(dirname($path), 0755, true);
        file_put_contents($path, $template);
        $this->info("Controller [{$controllerName}] created successfully. \n Path: {$path}");

        return 0;
    }
}
