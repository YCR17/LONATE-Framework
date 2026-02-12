<?php

namespace Aksa\Console\Commands;

use Aksa\Console\Command;
use Aksa\Database\Migrations\Migrator;

class MigrateCommand extends Command
{
    protected $description = 'Run the database migrations';

    public function handle($args = [])
    {
        $migrator = new Migrator();
        $migrator->run();
        echo "Migrations completed.\n";
    }
}
