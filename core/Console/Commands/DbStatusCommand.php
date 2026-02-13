<?php

namespace Lonate\Core\Console\Commands;

use Lonate\Core\Console\Command;
use Lonate\Core\Database\Manager;

class DbStatusCommand extends Command
{
    protected string $name = 'db:status';
    protected string $description = 'Show current database connection, driver, and grammar info';

    public function handle(): int
    {
        $app = $GLOBALS['app'] ?? null;
        if (!$app) {
            $this->error("Application not bootstrapped.");
            return 1;
        }

        $dbConfig = config('database') ?? [];
        $default = $dbConfig['default'] ?? env('DB_CONNECTION', 'unknown');

        $this->info("╔══════════════════════════════════════╗");
        $this->info("║  Database Status                     ║");
        $this->info("╚══════════════════════════════════════╝");
        $this->info("");

        $this->info("Default connection: {$default}");
        $this->info("");

        $connections = $dbConfig['connections'] ?? [];
        foreach ($connections as $name => $conn) {
            $driver = $conn['driver'] ?? 'unknown';
            $marker = ($name === $default) ? ' ← active' : '';
            $this->info("  [{$name}]{$marker}");
            $this->line("    Driver:  {$driver}");

            if ($driver === 'mysql') {
                $this->line("    Host:    " . ($conn['host'] ?? '127.0.0.1'));
                $this->line("    DB:      " . ($conn['database'] ?? '-'));
            } elseif ($driver === 'sawit') {
                $database = $conn['database'] ?? '-';
                $this->line("    File:    {$database}");
                if (is_string($database) && file_exists($database)) {
                    $this->line("    Size:    " . number_format(filesize($database)) . " bytes");
                }
            }

            // Show grammar
            try {
                $manager = $app->make(Manager::class);
                $connection = $manager->connection($name);
                $grammar = $connection->getGrammar();
                $grammarClass = (new \ReflectionClass($grammar))->getShortName();
                $this->line("    Grammar: {$grammarClass}");
            } catch (\Throwable $e) {
                $this->line("    Grammar: (unable to resolve)");
            }

            $this->info("");
        }

        return 0;
    }
}
