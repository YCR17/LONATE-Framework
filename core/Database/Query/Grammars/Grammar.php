<?php

namespace Lonate\Core\Database\Query\Grammars;

/**
 * Class Grammar
 * 
 * Base query grammar that compiles Builder state into SQL strings.
 * Each driver can extend this to produce dialect-specific SQL.
 * 
 * This base class produces generic SQL with `?` parameter placeholders
 * and no column/table quoting — suitable for InMemory driver.
 * 
 * @package Lonate\Core\Database\Query\Grammars
 */
class Grammar
{
    /**
     * Whether this grammar uses parameterized bindings (?).
     * If false, values are inlined directly into the SQL string.
     */
    protected bool $usesBindings = true;

    // ═══════════════════════════════════
    //  WRAPPING (column/table quoting)
    // ═══════════════════════════════════

    /**
     * Wrap a table name for the SQL dialect.
     */
    public function wrapTable(string $table): string
    {
        return $table;
    }

    /**
     * Wrap a column name for the SQL dialect.
     */
    public function wrapColumn(string $column): string
    {
        if ($column === '*') return '*';
        return $column;
    }

    /**
     * Produce a parameter placeholder or an inline value.
     * Base grammar uses `?` placeholder (PDO-style).
     */
    public function parameter(mixed $value): string
    {
        return '?';
    }

    /**
     * Whether this grammar uses `?` parameter bindings.
     */
    public function usesBindings(): bool
    {
        return $this->usesBindings;
    }

    // ═══════════════════════════════════
    //  COMPILATION
    // ═══════════════════════════════════

    /**
     * Compile a SELECT query.
     */
    public function compileSelect(string $table, array $columns): string
    {
        $cols = implode(', ', array_map(fn($c) => $this->wrapColumn($c), $columns));
        return "SELECT {$cols} FROM {$this->wrapTable($table)}";
    }

    /**
     * Compile an INSERT query.
     *
     * @return array{sql: string, bindings: array}
     */
    public function compileInsert(string $table, array $values): array
    {
        $columns = implode(', ', array_map(fn($c) => $this->wrapColumn($c), array_keys($values)));
        
        if ($this->usesBindings) {
            $placeholders = implode(', ', array_fill(0, count($values), '?'));
            $bindings = array_values($values);
        } else {
            $placeholders = implode(', ', array_map(fn($v) => $this->parameter($v), array_values($values)));
            $bindings = [];
        }

        $sql = "INSERT INTO {$this->wrapTable($table)} ({$columns}) VALUES ({$placeholders})";
        return ['sql' => $sql, 'bindings' => $bindings];
    }

    /**
     * Compile an UPDATE query's SET clause.
     *
     * @return array{sql: string, bindings: array}
     */
    public function compileUpdate(string $table, array $values): array
    {
        $setParts = [];
        $bindings = [];

        foreach ($values as $key => $val) {
            if ($this->usesBindings) {
                $setParts[] = "{$this->wrapColumn($key)} = ?";
                $bindings[] = $val;
            } else {
                $setParts[] = "{$this->wrapColumn($key)} = {$this->parameter($val)}";
            }
        }

        $setStr = implode(', ', $setParts);
        $sql = "UPDATE {$this->wrapTable($table)} SET {$setStr}";
        return ['sql' => $sql, 'bindings' => $bindings];
    }

    /**
     * Compile a DELETE query.
     */
    public function compileDelete(string $table): string
    {
        return "DELETE FROM {$this->wrapTable($table)}";
    }

    /**
     * Compile WHERE clauses.
     *
     * @return array{sql: string, bindings: array}
     */
    public function compileWheres(array $wheres): array
    {
        if (empty($wheres)) {
            return ['sql' => '', 'bindings' => []];
        }

        $clauses = [];
        $bindings = [];

        foreach ($wheres as $i => $where) {
            $boolean = $i === 0 ? '' : ' ' . ($where['boolean'] ?? 'AND') . ' ';
            $type = $where['type'] ?? 'basic';
            $col = $this->wrapColumn($where['column']);

            if ($type === 'null') {
                $clauses[] = $boolean . "{$col} IS NULL";
            } elseif ($type === 'not_null') {
                $clauses[] = $boolean . "{$col} IS NOT NULL";
            } else {
                if ($this->usesBindings) {
                    $clauses[] = $boolean . "{$col} {$where['operator']} ?";
                    $bindings[] = $where['value'];
                } else {
                    $clauses[] = $boolean . "{$col} {$where['operator']} {$this->parameter($where['value'])}";
                }
            }
        }

        return ['sql' => ' WHERE ' . implode('', $clauses), 'bindings' => $bindings];
    }

    /**
     * Compile ORDER BY clause.
     */
    public function compileOrderBy(array $orders): string
    {
        if (empty($orders)) return '';

        $parts = array_map(
            fn($o) => "{$this->wrapColumn($o['column'])} {$o['direction']}",
            $orders
        );

        return ' ORDER BY ' . implode(', ', $parts);
    }

    /**
     * Compile LIMIT/OFFSET clause.
     */
    public function compileLimit(?int $limit, ?int $offset): string
    {
        $sql = '';
        if ($limit !== null) {
            $sql .= " LIMIT {$limit}";
        }
        if ($offset !== null) {
            $sql .= " OFFSET {$offset}";
        }
        return $sql;
    }

    /**
     * Compile a CREATE TABLE statement.
     */
    public function compileCreateTable(string $table): string
    {
        return "CREATE TABLE {$this->wrapTable($table)}";
    }

    /**
     * Compile a DROP TABLE statement.
     */
    public function compileDropTable(string $table): string
    {
        return "DROP TABLE {$this->wrapTable($table)}";
    }

    /**
     * Compile a COUNT query.
     */
    public function compileCount(string $table): string
    {
        return "SELECT COUNT(*) as count FROM {$this->wrapTable($table)}";
    }

    /**
     * Quote/escape a value for inline SQL.
     * Used when usesBindings is false.
     */
    public function quoteValue(mixed $value): string
    {
        if (is_null($value)) return 'NULL';
        if (is_int($value) || is_float($value)) return (string) $value;
        if (is_bool($value)) return $value ? '1' : '0';

        $escaped = str_replace("'", "''", (string) $value);
        return "'{$escaped}'";
    }
}
