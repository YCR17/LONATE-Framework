<?php

namespace Lonate\Core\Database\Schema;

use Lonate\Core\Database\Manager;

class Schema
{
    public static function create(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);
        
        $sql = $blueprint->build();
        
        // Execute SQL
        // In a real framework, we'd resolve the connection from Manager
        // For skeleton, we'll try to resolve Manager via global app
        $app = $GLOBALS['app'] ?? \Lonate\Core\Support\Facade::getFacadeApplication();
        $manager = $app->make(Manager::class);
        
        // Use default connection
        $manager->connection()->query($sql);
        
        echo "Table '$table' created.\n";
    }
    
    public static function dropIfExists(string $table): void
    {
        $app = $GLOBALS['app'] ?? \Lonate\Core\Support\Facade::getFacadeApplication();
        $manager = $app->make(Manager::class);
        $manager->connection()->query("DROP TABLE IF EXISTS $table");
        
        echo "Table '$table' dropped.\n";
    }
}
