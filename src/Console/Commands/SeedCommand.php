<?php

namespace MiniLaravel\Console\Commands;

use MiniLaravel\Console\Command;

class SeedCommand extends Command
{
    protected $description = 'Run the database seeders';

    public function handle($args = [])
    {
        // support positional class or --class=Name
        $class = null;
        foreach ($args as $a) {
            if (strpos($a, '--class=') === 0) {
                $class = substr($a, 8);
            } elseif (!$class && strpos($a, '--') !== 0) {
                $class = $a;
            }
        }

        $path = dirname(__DIR__, 4) . '/database/seeders';

        if ($class) {
            $file = $path . '/' . $class . '.php';
            if (!file_exists($file)) {
                echo "Seeder {$class} not found.\n";
                return;
            }
            require_once $file;
            if (class_exists($class)) {
                $instance = new $class;
                if (method_exists($instance, 'run')) {
                    $instance->run();
                    echo "Seeded: {$class}\n";
                    return;
                }
            }
            echo "Seeder {$class} invalid.\n";
            return;
        }

        // If DatabaseSeeder exists, run it
        $dbSeederFile = $path . '/DatabaseSeeder.php';
        if (file_exists($dbSeederFile)) {
            require_once $dbSeederFile;
            if (class_exists('DatabaseSeeder')) {
                $instance = new \DatabaseSeeder();
                if (method_exists($instance, 'run')) {
                    $instance->run();
                    echo "Seeded: DatabaseSeeder\n";
                    return;
                }
            }
        }

        // fallback: run all seeders
        if (!is_dir($path)) {
            echo "No seeders found.\n";
            return;
        }

        foreach (glob($path . '/*.php') as $file) {
            require_once $file;
            $name = basename($file, '.php');
            if (class_exists($name)) {
                $instance = new $name;
                if (method_exists($instance, 'run')) {
                    $instance->run();
                    echo "Seeded: {$name}\n";
                }
            }
        }
    }
}
