<?php

namespace MiniLaravel\Console\Commands;

use MiniLaravel\Console\Command;
use MiniLaravel\Database\Migrations\Migrator;

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
