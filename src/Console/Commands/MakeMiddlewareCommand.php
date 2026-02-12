<?php

namespace MiniLaravel\Console\Commands;

use MiniLaravel\Console\Command;

class MakeMiddlewareCommand extends Command
{
    protected $description = 'Create a new middleware class';

    public function handle($args = [])
    {
        $name = $args[0] ?? null;
        if (!$name) {
            $this->error("Usage: php artisan make:middleware MiddlewareName");
            return;
        }

        $className = $name;
        $stub = "<?php\n\nnamespace App\\Http\\Middleware;\n\nuse MiniLaravel\\Http\\Request;\n\nclass {$className}\n{\n    public function handle(Request \$request, \$next)\n    {\n        // TODO: implement middleware logic\n        return \$next(\$request);\n    }\n}\n";

        $path = $this->app->basePath() . '/app/Http/Middleware';
        if (!is_dir($path)) mkdir($path, 0755, true);

        $file = $path . '/' . $className . '.php';
        file_put_contents($file, $stub);

        $this->info("Created middleware: {$className}.php");
    }
}
