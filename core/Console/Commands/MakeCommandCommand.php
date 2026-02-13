<?php

namespace Lonate\Core\Console\Commands;

use Lonate\Core\Console\Command;

class MakeCommandCommand extends Command
{
    protected string $name = 'make:command';
    protected string $description = 'Create a new artisan command class';

    public function handle(): int
    {
        $commandName = $this->argument(0);

        if (!$commandName) {
            $this->error('Please provide a command name.');
            return 1;
        }

        // Derive a CLI name from the class name, e.g. SendEmailCommand → send:email
        $cliName = $this->argument(1, 'app:' . strtolower(
            preg_replace('/Command$/', '', preg_replace('/([a-z])([A-Z])/', '$1-$2', $commandName))
        ));

        $template = <<<PHP
<?php

namespace App\Console\Commands;

use Lonate\Core\Console\Command;

class {$commandName} extends Command
{
    protected string \$name = '{$cliName}';
    protected string \$description = 'Description of {$commandName}';

    public function handle(): int
    {
        \$this->info('Hello from {$commandName}!');

        // Your command logic here...

        return 0;
    }
}
PHP;

        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);
        $path = $basePath . "/app/Console/Commands/{$commandName}.php";

        if (file_exists($path) && !$this->option('force')) {
            $this->error("Command [{$commandName}] already exists! Use --force to overwrite.");
            return 1;
        }

        @mkdir(dirname($path), 0755, true);
        file_put_contents($path, $template);
        $this->info("Command [{$commandName}] created at: app/Console/Commands/{$commandName}.php");
        $this->line("  CLI name: {$cliName}");
        $this->line("  Register in core/Console/Kernel.php to activate.");

        return 0;
    }
}
