<?php

namespace Aksa\Console\Commands;

use Aksa\Console\Command;

class MakeControllerCommand extends Command
{
    protected $description = 'Create a new controller class';

    public function handle($args = [])
    {
        $name = $args[0] ?? null;
        if (!$name) {
            echo "Usage: php artisan make:controller ControllerName\n";
            return;
        }

        $className = $name;
        $stub = "<?php\n\nnamespace App\\Http\\Controllers;\n\nuse Aksa\\Http\\Controller;\n\nclass {$className} extends Controller\n{\n    public function index()\n    {\n        // TODO: implement\n    }\n}\n";

        $path = dirname(__DIR__, 5) . '/app/Http/Controllers';
        if (!is_dir($path)) mkdir($path, 0755, true);

        $file = $path . '/' . $className . '.php';
        file_put_contents($file, $stub);

        echo "Created controller: {$className}.php\n";
    }
}
