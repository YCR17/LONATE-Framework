<?php

namespace MiniLaravel\Console;

use MiniLaravel\Support\Application;

class Kernel
{
    protected $app;
    protected $commands = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->registerCommands();
    }

    protected function registerCommands()
    {
        $this->commands = [
            'migrate' => Commands\MigrateCommand::class,
            'migrate:rollback' => Commands\MigrateRollbackCommand::class,
            'make:migration' => Commands\MakeMigrationCommand::class,
            'make:seeder' => Commands\MakeSeederCommand::class,
            'db:seed' => Commands\SeedCommand::class,
            'make:controller' => Commands\MakeControllerCommand::class,
            'make:model' => Commands\MakeModelCommand::class,
            'make:middleware' => Commands\MakeMiddlewareCommand::class,
            'serve' => Commands\ServeCommand::class,
        ];
    }

    public function handle($argv)
    {
        $command = $argv[1] ?? null;
        $args = array_slice($argv, 2);

        if (!$command) {
            $this->listCommands();
            return;
        }

        // handle helper commands
        if (in_array($command, ['list', 'help'])) {
            $this->listCommands();
            return;
        }

        if (!isset($this->commands[$command])) {
            echo "\033[31mCommand '{$command}' not found.\033[0m\n";
            $this->listCommands();
            return;
        }

        try {
            $class = $this->commands[$command];
            $instance = new $class($this->app);
            $instance->handle($args);
        } catch (\Throwable $e) {
            if (class_exists('\App\Exceptions\Handler')) {
                $handler = new \App\Exceptions\Handler();
                $handler->report($e);
                $handler->renderForConsole($e);
            } else {
                echo "Error: " . $e->getMessage() . "\n";
            }
        }
    }

    protected function listCommands()
    {
        echo "Available commands:\n";
        $max = 0;
        foreach (array_keys($this->commands) as $name) {
            $len = strlen($name);
            if ($len > $max) $max = $len;
        }

        foreach ($this->commands as $name => $class) {
            $instance = new $class($this->app);
            $desc = method_exists($instance, 'getDescription') ? $instance->getDescription() : '';
            printf("  %-{$max}s   %s\n", $name, $desc);
        }
    }
}
