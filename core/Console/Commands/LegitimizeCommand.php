<?php

namespace Lonate\Core\Console\Commands;

use Lonate\Core\Console\Command;
use Lonate\Core\Legitimacy\Engine;

class LegitimizeCommand extends Command
{
    protected string $name = 'legitimize';
    protected string $description = 'Issue a legitimacy policy approval';

    public function handle(): int
    {
        $this->info("Attempting to legitimize policy...");

        $user = $this->argument(0, 'Admin');
        $resource = $this->argument(1, 'LegacyMonolith');

        // Use framework's legitimacy engine
        $app = $GLOBALS['app'] ?? null;
        if (!$app) {
            $this->error("Application not bootstrapped.");
            return 1;
        }

        $engine = $app->make(Engine::class);
        $approved = $engine->declareQuorum([$user], 1);

        if ($approved) {
            $this->info("[SUCCESS] Policy approved for '{$resource}' by '{$user}'.");
            $this->line("Board Resolution evidence verified.");
            return 0;
        }

        $this->error("[FAILED] Legitimacy denied.");
        return 1;
    }
}
