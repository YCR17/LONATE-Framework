<?php

namespace MiniLaravel\Database;

use MiniLaravel\Database\DatabaseManager;
use MiniLaravel\Database\Blueprint;

class Schema
{
    protected static $connection;

    public static function setConnection($connection)
    {
        static::$connection = $connection;
    }

    protected static function getConnection()
    {
        if (!static::$connection) {
            static::$connection = DatabaseManager::getInstance()->getConnection();
        }

        return static::$connection;
    }

    public static function create($table, \Closure $callback)
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);
        $sql = $blueprint->toSql();
        $conn = static::getConnection();
        $conn->exec($sql);
    }

    public static function dropIfExists($table)
    {
        $conn = static::getConnection();
        $conn->exec("DROP TABLE IF EXISTS {$table}");
    }

    public static function table($table, \Closure $callback)
    {
        // Basic support for adding columns (not fully implemented)
        $blueprint = new Blueprint($table);
        $callback($blueprint);
        $sqls = $blueprint->alterSql();
        $conn = static::getConnection();
        foreach ($sqls as $sql) {
            $conn->exec($sql);
        }
    }
}
