<?php

namespace Aksa\Console\Commands;

use Aksa\Console\Command;

class MakeModelCommand extends Command
{
    protected $description = 'Create a new model class';

    public function handle($args = [])
    {
        $name = $args[0] ?? null;
        if (!$name) {
            echo "Usage: php artisan make:model ModelName\n";
            return;
        }

        $className = $name;
        $stub = "<?php\n\nnamespace App\\Models;\n\nuse Aksa\\Database\\Model;\n\nclass {$className} extends Model\n{\n    protected \$fillable = [];\n}\n";

        $path = dirname(__DIR__, 5) . '/app/Models';
        if (!is_dir($path)) mkdir($path, 0755, true);

        $file = $path . '/' . $className . '.php';
        file_put_contents($file, $stub);

        echo "Created model: {$className}.php\n";
    }
}
