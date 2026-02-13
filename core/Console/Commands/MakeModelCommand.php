<?php

namespace Lonate\Core\Console\Commands;

use Lonate\Core\Console\Command;

class MakeModelCommand extends Command
{
    protected string $name = 'make:model';
    protected string $description = 'Create a new model class';

    public function handle(): int
    {
        $modelName = $this->argument(0);

        if (!$modelName) {
            $this->error('Please provide a model name.');
            return 1;
        }

        $template = <<<PHP
<?php

namespace App\Models;

use Lonate\Core\Database\Model;

class {$modelName} extends Model
{
    protected ?string \$table = null;

    protected \$fillable = [];
}
PHP;

        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);
        $path = $basePath . "/app/Models/{$modelName}.php";

        if (file_exists($path) && !$this->option('force')) {
            $this->error("Model [{$modelName}] already exists! Use --force to overwrite.");
            return 1;
        }

        @mkdir(dirname($path), 0755, true);
        file_put_contents($path, $template);
        $this->info("Model [{$modelName}] created successfully.");

        return 0;
    }
}
