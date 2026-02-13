<?php

namespace Lonate\Core\Console\Commands;

use Lonate\Core\Console\Command;

class MigrateCommand extends Command
{
    protected string $name = 'migrate';
    protected string $description = 'Run the database migrations';

    public function handle(): int
    {
        $this->info("Starting migration...");
        
        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);
        $path = $basePath . '/database/migrations';

        if (!is_dir($path)) {
            $this->warn("No migrations directory found at: {$path}");
            return 0;
        }

        $files = glob($path . '/*.php');
        
        if (empty($files)) {
            $this->info("Nothing to migrate.");
            return 0;
        }

        foreach ($files as $file) {
            require_once $file;
            $className = $this->getClassNameFromFile($file);
            
            if ($className && class_exists($className)) {
                $migration = new $className();
                $this->line("Migrating: " . basename($file));
                try {
                    $migration->up();
                    $this->info("Migrated:  " . basename($file));
                } catch (\Exception $e) {
                    $this->error("Failed:    " . basename($file) . " — " . $e->getMessage());
                }
            }
        }
        
        return 0;
    }
    
    protected function getClassNameFromFile(string $file): ?string
    {
        $content = file_get_contents($file);
        if (preg_match('/class\s+(\w+)/', $content, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
