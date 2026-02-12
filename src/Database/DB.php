<?php

namespace MiniLaravel\Database;

use MiniLaravel\Database\DatabaseManager;

class DB
{
    public static function table($table)
    {
        return DatabaseManager::getInstance()->table($table);
    }

    public static function connection()
    {
        return DatabaseManager::getInstance()->getConnection();
    }
}
