<?php

namespace Aksa\Console\Commands;

use Aksa\Console\Command;

class MakeSeederCommand extends Command
{
    protected $description = 'Create a new seeder class';

    public function handle($args = [])
    {
        $name = $args[0] ?? null;
        if (!$name) {
            echo "Usage: php artisan make:seeder SeederName\n";
            return;
        }

        $className = $name;
        $stub = "<?php\n\nuse Aksa\\Database\\Seeder;\nuse Aksa\\Database\\DB;\n\nclass {$className} extends Seeder\n{\n    public function run()\n    {\n        // Example: DB::table('users')->insert([\n        //     ['name' => 'Admin', 'email' => 'admin@example.com', 'password' => password_hash('password', PASSWORD_DEFAULT)],\n        // ]);\n    }\n}\n";

        $path = dirname(__DIR__, 4) . '/database/seeders';
        if (!is_dir($path)) mkdir($path, 0755, true);

        $file = $path . '/' . $className . '.php';
        file_put_contents($file, $stub);

        echo "Created seeder: {$className}.php\n";
    }
}
