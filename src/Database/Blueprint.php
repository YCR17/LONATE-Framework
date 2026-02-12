<?php

namespace Aksa\Database;

class Blueprint
{
    protected $table;
    protected $columns = [];
    protected $alterCommands = [];

    public function __construct($table)
    {
        $this->table = $table;
    }

    public function increments($column = 'id')
    {
        $this->columns[] = "{$column} INT AUTO_INCREMENT PRIMARY KEY";
    }

    public function bigIncrements($column = 'id')
    {
        $this->columns[] = "{$column} BIGINT AUTO_INCREMENT PRIMARY KEY";
    }

    public function string($column, $length = 255)
    {
        $this->columns[] = "{$column} VARCHAR({$length}) NOT NULL";
    }

    public function text($column)
    {
        $this->columns[] = "{$column} TEXT";
    }

    public function integer($column, $unsigned = false)
    {
        $col = "{$column} INT" . ($unsigned ? ' UNSIGNED' : '');
        $this->columns[] = $col;
    }

    public function boolean($column)
    {
        $this->columns[] = "{$column} TINYINT(1) DEFAULT 0";
    }

    public function timestamps()
    {
        $this->columns[] = "created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP";
        $this->columns[] = "updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
    }

    public function nullableTimestamps()
    {
        $this->columns[] = "created_at TIMESTAMP NULL";
        $this->columns[] = "updated_at TIMESTAMP NULL";
    }

    public function foreignId($column)
    {
        $this->columns[] = "{$column} BIGINT UNSIGNED";
    }

    public function toSql()
    {
        $columnsSql = implode(",\n    ", $this->columns);
        return "CREATE TABLE IF NOT EXISTS {$this->table} (\n    {$columnsSql}\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    }

    public function alterSql()
    {
        // Basic alter support: add columns
        $sqls = [];
        foreach ($this->columns as $col) {
            $sqls[] = "ALTER TABLE {$this->table} ADD COLUMN {$col}";
        }

        return $sqls;
    }
}
