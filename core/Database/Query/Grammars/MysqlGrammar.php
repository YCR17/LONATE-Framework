<?php

namespace Lonate\Core\Database\Query\Grammars;

/**
 * Class MysqlGrammar
 * 
 * MySQL-specific grammar. Wraps table and column names in backticks
 * and uses `?` parameter bindings (handled by PDO).
 * 
 * @package Lonate\Core\Database\Query\Grammars
 */
class MysqlGrammar extends Grammar
{
    protected bool $usesBindings = true;

    public function wrapTable(string $table): string
    {
        return "`{$table}`";
    }

    public function wrapColumn(string $column): string
    {
        if ($column === '*') return '*';

        // Handle alias: column AS alias
        if (stripos($column, ' as ') !== false) {
            [$col, $alias] = preg_split('/\s+as\s+/i', $column, 2);
            return "`" . trim($col) . "` AS `" . trim($alias) . "`";
        }

        // Handle table.column
        if (str_contains($column, '.')) {
            [$tbl, $col] = explode('.', $column, 2);
            return "`{$tbl}`.`{$col}`";
        }

        return "`{$column}`";
    }

    /**
     * MySQL CREATE TABLE needs column definitions.
     * For basic usage, create with a single auto-increment id column.
     */
    public function compileCreateTable(string $table): string
    {
        return "CREATE TABLE IF NOT EXISTS `{$table}` ("
            . "`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY"
            . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    }

    public function compileDropTable(string $table): string
    {
        return "DROP TABLE IF EXISTS `{$table}`";
    }
}
