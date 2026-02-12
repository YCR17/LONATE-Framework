<?php

namespace Aksa\Database;

use Aksa\Database\DatabaseManager;

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
