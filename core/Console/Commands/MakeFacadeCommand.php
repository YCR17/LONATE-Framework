<?php

namespace Lonate\Core\Console\Commands;

use Lonate\Core\Console\Command;

class MakeFacadeCommand extends Command
{
    protected string $name = 'make:facade';
    protected string $description = 'Create a new facade class';

    public function handle(): int
    {
        $facadeName = $this->argument(0);

        if (!$facadeName) {
            $this->error('Please provide a facade name.');
            return 1;
        }

        $accessor = $this->argument(1, "App\\Services\\{$facadeName}");

        $template = <<<PHP
<?php

namespace App\Facades;

use Lonate\Core\Support\Facade;

class {$facadeName} extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \\{$accessor}::class;
    }
}
PHP;

        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);
        $path = $basePath . "/app/Facades/{$facadeName}.php";

        if (file_exists($path) && !$this->option('force')) {
            $this->error("Facade [{$facadeName}] already exists! Use --force to overwrite.");
            return 1;
        }

        @mkdir(dirname($path), 0755, true);
        file_put_contents($path, $template);
        $this->info("Facade [{$facadeName}] created successfully.");

        return 0;
    }
}
