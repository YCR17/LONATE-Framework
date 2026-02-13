<?php

namespace Lonate\Core\Console\Commands;

use Lonate\Core\Console\Command;
use SawitDB\Engine\WowoEngine;

class SawitCreateCommand extends Command
{
    protected string $name = 'sawit:create';
    protected string $description = 'Create a new .sawit database file';

    public function handle(): int
    {
        $dbName = $this->argument(0, 'plantation');
        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);
        $dbDir = $basePath . '/database';
        $dbPath = $dbDir . '/' . $dbName . '.sawit';

        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        if (file_exists($dbPath) && !$this->option('force')) {
            $this->warn("Database [{$dbName}.sawit] already exists. Use --force to overwrite.");
            return 1;
        }

        if (file_exists($dbPath) && $this->option('force')) {
            unlink($dbPath);
        }

        // Initialize the .sawit file via WowoEngine
        $engine = new WowoEngine($dbPath);
        
        $this->info("SawitDB database created: {$dbPath}");
        $this->info("File size: " . number_format(filesize($dbPath)) . " bytes");

        return 0;
    }
}
