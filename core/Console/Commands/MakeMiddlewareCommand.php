<?php

namespace Lonate\Core\Console\Commands;

use Lonate\Core\Console\Command;

class MakeMiddlewareCommand extends Command
{
    protected string $name = 'make:middleware';
    protected string $description = 'Create a new middleware class';

    public function handle(): int
    {
        $middlewareName = $this->argument(0);

        if (!$middlewareName) {
            $this->error('Please provide a middleware name.');
            return 1;
        }

        $template = <<<PHP
<?php

namespace App\Http\Middleware;

use Lonate\Core\Http\Request;
use Lonate\Core\Http\Response;

class {$middlewareName}
{
    /**
     * Handle the incoming request.
     *
     * @param Request \$request
     * @param callable \$next
     * @return Response
     */
    public function handle(Request \$request, callable \$next): Response
    {
        // Before middleware logic...

        \$response = \$next(\$request);

        // After middleware logic...

        return \$response;
    }
}
PHP;

        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);
        $path = $basePath . "/app/Http/Middleware/{$middlewareName}.php";

        if (file_exists($path) && !$this->option('force')) {
            $this->error("Middleware [{$middlewareName}] already exists! Use --force to overwrite.");
            return 1;
        }

        @mkdir(dirname($path), 0755, true);
        file_put_contents($path, $template);
        $this->info("Middleware [{$middlewareName}] created successfully.");

        return 0;
    }
}
