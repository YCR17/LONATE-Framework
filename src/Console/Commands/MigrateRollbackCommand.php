<?php

namespace Aksa\Console\Commands;

use Aksa\Console\Command;
use Aksa\Database\Migrations\Migrator;

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
