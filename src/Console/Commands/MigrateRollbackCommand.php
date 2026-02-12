<?php

namespace MiniLaravel\Console\Commands;

use MiniLaravel\Console\Command;
use MiniLaravel\Database\Migrations\Migrator;

class MigrateRollbackCommand extends Command
{
    protected $description = 'Rollback the last migration batch';

    public function handle($args = [])
    {
        $migrator = new Migrator();
        $migrator->rollback();
        echo "Rollback completed.\n";
    }
}
