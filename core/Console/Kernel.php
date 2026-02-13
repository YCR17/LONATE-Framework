<?php

namespace Lonate\Core\Console;

use Lonate\Core\Foundation\Application;

/**
 * Class Kernel
 * 
 * Console kernel that manages artisan commands.
 * 
 * Core commands are registered here. Users can add custom commands
 * via registerCommands() or by creating classes in app/Console/Commands/.
 * 
 * @package Lonate\Core\Console
 */
class Kernel
{
    protected Application $app;
    
    /**
     * Core framework commands.
     */
    protected array $commands = [
        // Scaffolding
        Commands\MakeModelCommand::class,
        Commands\MakeControllerCommand::class,
        Commands\MakeMiddlewareCommand::class,
        Commands\MakeCommandCommand::class,
        Commands\MakeFacadeCommand::class,

        // Database
        Commands\MigrateCommand::class,
        Commands\DbStatusCommand::class,

        // SawitDB
        Commands\SawitCreateCommand::class,
        Commands\SawitStatusCommand::class,
        Commands\SawitQueryCommand::class,

        // Framework
        Commands\LegitimizeCommand::class,
        Commands\ServeCommand::class,
    ];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Handle an incoming console command.
     *
     * @param array $argv
     * @return int Exit code
     */
    public function handle(array $argv): int
    {
        // Auto-discover user commands from app/Console/Commands/
        $this->discoverUserCommands();

        $commandName = $argv[1] ?? 'list';
        $commandArgs = array_slice($argv, 2);
        
        if ($commandName === 'list') {
            $this->listCommands();
            return 0;
        }

        foreach ($this->commands as $commandClass) {
            $command = $this->app->make($commandClass);
            if ($command->getName() === $commandName) {
                $command->setInput($commandArgs);
                return $command->handle();
            }
        }

        echo "Command \"{$commandName}\" not found.\n";
        echo "Run 'php artisan list' to see available commands.\n";
        return 1;
    }

    /**
     * Register additional commands (for user/package registration).
     *
     * @param array $commands
     * @return void
     */
    public function registerCommands(array $commands): void
    {
        $this->commands = array_merge($this->commands, $commands);
    }

    /**
     * Auto-discover user commands from app/Console/Commands/.
     */
    protected function discoverUserCommands(): void
    {
        $basePath = $this->app->basePath();
        $commandDir = $basePath . '/app/Console/Commands';

        if (!is_dir($commandDir)) {
            return;
        }

        $files = glob($commandDir . '/*.php');
        foreach ($files as $file) {
            $className = 'App\\Console\\Commands\\' . pathinfo($file, PATHINFO_FILENAME);
            if (class_exists($className) && !in_array($className, $this->commands)) {
                $this->commands[] = $className;
            }
        }
    }

    /**
     * List all available commands grouped by category.
     */
    protected function listCommands(): void
    {
        echo "\n\033[1mLONATE Framework " . Application::VERSION . "\033[0m\n\n";
        echo "\033[33mUsage:\033[0m command [options] [arguments]\n\n";
        echo "\033[33mAvailable commands:\033[0m\n";

        // Group commands by prefix
        $groups = [];
        foreach ($this->commands as $commandClass) {
            $command = new $commandClass();
            $name = $command->getName();
            $parts = explode(':', $name);
            $group = count($parts) > 1 ? $parts[0] : '_default';
            $groups[$group][] = $command;
        }

        // Sort groups
        ksort($groups);

        foreach ($groups as $group => $commands) {
            if ($group !== '_default') {
                echo " \033[33m{$group}\033[0m\n";
            }
            foreach ($commands as $command) {
                echo "  \033[32m" . str_pad($command->getName(), 25) . "\033[0m " . $command->getDescription() . "\n";
            }
        }
        echo "\n";
    }
}
