<?php

namespace Lonate\Core\Console\Commands;

use Lonate\Core\Console\Command;

class ServeCommand extends Command
{
    protected string $name = 'serve';
    protected string $description = 'Start the PHP development server';

    public function handle(): int
    {
        $host = $this->argument(0, '127.0.0.1');
        $port = $this->argument(1, '8000');
        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);
        $docroot = $basePath . '/public';

        if (!is_dir($docroot)) {
            $this->error("Document root not found: {$docroot}");
            return 1;
        }

        $this->info("LONATE Development Server");
        $this->info("Listening on: http://{$host}:{$port}");
        $this->line("Document root: {$docroot}");
        $this->line("Press Ctrl+C to stop.");
        $this->info("");

        $command = sprintf(
            'php -S %s:%s -t %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($docroot)
        );

        // passthru keeps the process attached to the terminal
        passthru($command, $exitCode);

        return $exitCode;
    }
}
