<?php

namespace Aksa\Console\Commands;

use Aksa\Console\Command;

class MakeMigrationCommand extends Command
{
    protected $description = 'Create a new migration file';

    public function handle($args = [])
    {
        // parse args and options (--create=table, --table=table)
        $name = null;
        $options = [];

        foreach ($args as $a) {
            if (strpos($a, '--') === 0) {
                $parts = explode('=', $a, 2);
                $opt = ltrim($parts[0], '-');
                $options[$opt] = $parts[1] ?? true;
            } else {
                if (!$name) $name = $a;
            }
        }

        if (!$name) {
            echo "Usage: php artisan make:migration name [--create=table] [--table=table]\n";
            return;
        }

        $timestamp = date('Y_m_d_His');
        $fileName = $timestamp . '_' . $name . '.php';
        $className = $this->classNameFromName($name);

        // determine whether this is create or alter
        $tableName = null;
        $isCreate = false;

        if (isset($options['create'])) {
            $tableName = $options['create'];
            $isCreate = true;
        } elseif (isset($options['table'])) {
            $tableName = $options['table'];
            $isCreate = false;
        } elseif (strpos($name, 'create_') === 0) {
            $tableName = str_replace(['create_', '_table'], ['', ''], $name);
            $isCreate = true;
        }

        if ($isCreate && $tableName) {
            $stub = "<?php\n\nuse Aksa\\Database\\Schema;\nuse Aksa\\Database\\Blueprint;\nuse Aksa\\Database\\Migration;\n\nclass {$className} extends Migration\n{\n    public function up()\n    {\n        Schema::create('{$tableName}', function (Blueprint \$table) {\n            // \$table->increments('id');\n            // \$table->string('name');\n            // \$table->timestamps();\n        });\n    }\n\n    public function down()\n    {\n        Schema::dropIfExists('{$tableName}');\n    }\n}\n";
        } elseif ($tableName) {
            $stub = "<?php\n\nuse Aksa\\Database\\Schema;\nuse Aksa\\Database\\Blueprint;\nuse Aksa\\Database\\Migration;\n\nclass {$className} extends Migration\n{\n    public function up()\n    {\n        Schema::table('{$tableName}', function (Blueprint \$table) {\n            // \$table->string('new_column');\n        });\n    }\n\n    public function down()\n    {\n        Schema::table('{$tableName}', function (Blueprint \$table) {\n            // \$table->dropColumn('new_column');\n        });\n    }\n}\n";
        } else {
            $stub = "<?php\n\nuse Aksa\\Database\\Migration;\n\nclass {$className} extends Migration\n{\n    public function up()\n    {\n        // TODO: implement migration\n    }\n\n    public function down()\n    {\n        // TODO: implement rollback\n    }\n}\n";
        }

        $path = dirname(__DIR__, 4) . '/database/migrations';
        if (!is_dir($path)) mkdir($path, 0755, true);

        file_put_contents($path . '/' . $fileName, $stub);

        echo "Created migration: {$fileName}\n";
    }

    protected function classNameFromName($name)
    {
        $parts = preg_split('/[_-]/', $name);
        $parts = array_map('ucfirst', $parts);
        return implode('', $parts);
    }
}
