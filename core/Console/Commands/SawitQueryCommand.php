<?php

namespace Lonate\Core\Console\Commands;

use Lonate\Core\Console\Command;
use SawitDB\Engine\WowoEngine;

class SawitQueryCommand extends Command
{
    protected string $name = 'sawit:query';
    protected string $description = 'Execute raw SQL or AQL against a .sawit database';

    public function handle(): int
    {
        $query = $this->argument(0);

        if (!$query) {
            $this->error('Usage: php artisan sawit:query "SELECT * FROM users"');
            $this->line('  Supports both standard SQL and SawitDB AQL syntax.');
            return 1;
        }

        $dbName = $this->argument(1, 'plantation');
        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);
        $dbPath = $basePath . '/database/' . $dbName . '.sawit';

        if (!file_exists($dbPath)) {
            $this->error("Database [{$dbName}.sawit] not found.");
            $this->info("Run: php artisan sawit:create {$dbName}");
            return 1;
        }

        $engine = new WowoEngine($dbPath);

        $this->line("Executing on: {$dbName}.sawit");
        $this->line("Query: {$query}");
        $this->info("");

        $result = $engine->query($query);

        if (is_array($result)) {
            if (empty($result)) {
                $this->warn("Empty result set.");
            } else {
                // Print as table
                $headers = array_keys($result[0]);
                $this->printTable($headers, $result);
                $this->info("");
                $this->info(count($result) . " row(s) returned.");
            }
        } elseif (is_string($result)) {
            $this->info($result);
        } else {
            $this->info("Query executed successfully.");
        }

        return 0;
    }

    /**
     * Print a simple ASCII table.
     */
    protected function printTable(array $headers, array $rows): void
    {
        // Calculate column widths
        $widths = [];
        foreach ($headers as $h) {
            $widths[$h] = strlen($h);
        }
        foreach ($rows as $row) {
            foreach ($headers as $h) {
                $val = (string) ($row[$h] ?? '');
                $widths[$h] = max($widths[$h], strlen($val));
            }
        }

        // Header
        $line = '+';
        foreach ($headers as $h) {
            $line .= str_repeat('-', $widths[$h] + 2) . '+';
        }

        echo $line . "\n";
        echo '|';
        foreach ($headers as $h) {
            echo ' ' . str_pad($h, $widths[$h]) . ' |';
        }
        echo "\n";
        echo $line . "\n";

        // Rows
        foreach ($rows as $row) {
            echo '|';
            foreach ($headers as $h) {
                echo ' ' . str_pad((string) ($row[$h] ?? ''), $widths[$h]) . ' |';
            }
            echo "\n";
        }
        echo $line . "\n";
    }
}
