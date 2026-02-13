<?php

namespace Lonate\Core\Console\Commands;

use Lonate\Core\Console\Command;
use SawitDB\Engine\WowoEngine;

class SawitStatusCommand extends Command
{
    protected string $name = 'sawit:status';
    protected string $description = 'Show tables and indexes in a .sawit database';

    public function handle(): int
    {
        $dbName = $this->argument(0, 'plantation');
        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);
        $dbPath = $basePath . '/database/' . $dbName . '.sawit';

        if (!file_exists($dbPath)) {
            $this->error("Database [{$dbName}.sawit] not found at: {$dbPath}");
            $this->info("Run: php artisan sawit:create {$dbName}");
            return 1;
        }

        $engine = new WowoEngine($dbPath);

        $this->info("╔══════════════════════════════════════╗");
        $this->info("║  SawitDB Status: {$dbName}.sawit");
        $this->info("╚══════════════════════════════════════╝");
        $this->info("");
        $this->info("File: {$dbPath}");
        $this->info("Size: " . number_format(filesize($dbPath)) . " bytes");
        $this->info("");

        // Show tables
        $tables = $engine->showTables();
        if (is_array($tables) && !empty($tables)) {
            $this->info("Tables (" . count($tables) . "):");
            foreach ($tables as $table) {
                $tableName = is_array($table) ? ($table['name'] ?? json_encode($table)) : $table;
                echo "  • {$tableName}\n";
            }
        } else {
            $this->warn("No tables found.");
        }

        // Show indexes
        $this->info("");
        $indexes = $engine->showIndexes(null);
        if (is_array($indexes) && !empty($indexes)) {
            $this->info("Indexes (" . count($indexes) . "):");
            foreach ($indexes as $idx) {
                $idxStr = is_array($idx) ? json_encode($idx) : $idx;
                echo "  • {$idxStr}\n";
            }
        } else {
            $this->warn("No indexes found.");
        }

        return 0;
    }
}
