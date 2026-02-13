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

    public function wrapTable(string $table): string
    {
        return $table;
    }

    public function wrapColumn(string $column): string
    {
        if ($column === '*') return '*';
        return $column;
    }

    public function parameter(mixed $value): string
    {
        return '?';
    }

    public function usesBindings(): bool
    {
        return $this->usesBindings;
    }

    // ═══════════════════════════════════
    //  SELECT COMPILATION
    // ═══════════════════════════════════

    public function compileSelect(string $table, array $columns, bool $distinct = false): string
    {
        $cols = implode(', ', array_map(fn($c) => $this->wrapColumn($c), $columns));
        $distinctStr = $distinct ? 'DISTINCT ' : '';
        return "SELECT {$distinctStr}{$cols} FROM {$this->wrapTable($table)}";
    }

    // ═══════════════════════════════════
    //  INSERT / UPDATE / DELETE
    // ═══════════════════════════════════

    /**
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
     * @return array{sql: string, bindings: array}
     */
    public function compileUpdate(string $table, array $values): array
    {
        $setParts = [];
        $bindings = [];

        foreach ($values as $key => $val) {
            // Raw expression support
            if (is_object($val) && method_exists($val, '__toString')) {
                $setParts[] = "{$this->wrapColumn($key)} = {$val}";
            } elseif ($this->usesBindings) {
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

    public function compileDelete(string $table): string
    {
        return "DELETE FROM {$this->wrapTable($table)}";
    }

    // ═══════════════════════════════════
    //  WHERE COMPILATION
    // ═══════════════════════════════════

    /**
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

            switch ($type) {
                case 'null':
                    $clauses[] = $boolean . "{$col} IS NULL";
                    break;

                case 'not_null':
                    $clauses[] = $boolean . "{$col} IS NOT NULL";
                    break;

                case 'in':
                    $values = $where['values'];
                    if ($this->usesBindings) {
                        $placeholders = implode(', ', array_fill(0, count($values), '?'));
                        $bindings = array_merge($bindings, $values);
                    } else {
                        $placeholders = implode(', ', array_map(fn($v) => $this->parameter($v), $values));
                    }
                    $clauses[] = $boolean . "{$col} IN ({$placeholders})";
                    break;

                case 'not_in':
                    $values = $where['values'];
                    if ($this->usesBindings) {
                        $placeholders = implode(', ', array_fill(0, count($values), '?'));
                        $bindings = array_merge($bindings, $values);
                    } else {
                        $placeholders = implode(', ', array_map(fn($v) => $this->parameter($v), $values));
                    }
                    $clauses[] = $boolean . "{$col} NOT IN ({$placeholders})";
                    break;

                case 'between':
                    if ($this->usesBindings) {
                        $clauses[] = $boolean . "{$col} BETWEEN ? AND ?";
                        $bindings[] = $where['values'][0];
                        $bindings[] = $where['values'][1];
                    } else {
                        $min = $this->parameter($where['values'][0]);
                        $max = $this->parameter($where['values'][1]);
                        $clauses[] = $boolean . "{$col} BETWEEN {$min} AND {$max}";
                    }
                    break;

                case 'not_between':
                    if ($this->usesBindings) {
                        $clauses[] = $boolean . "{$col} NOT BETWEEN ? AND ?";
                        $bindings[] = $where['values'][0];
                        $bindings[] = $where['values'][1];
                    } else {
                        $min = $this->parameter($where['values'][0]);
                        $max = $this->parameter($where['values'][1]);
                        $clauses[] = $boolean . "{$col} NOT BETWEEN {$min} AND {$max}";
                    }
                    break;

                default: // basic
                    if ($this->usesBindings) {
                        $clauses[] = $boolean . "{$col} {$where['operator']} ?";
                        $bindings[] = $where['value'];
                    } else {
                        $clauses[] = $boolean . "{$col} {$where['operator']} {$this->parameter($where['value'])}";
                    }
                    break;
            }
        }

        return ['sql' => ' WHERE ' . implode('', $clauses), 'bindings' => $bindings];
    }

    // ═══════════════════════════════════
    //  JOIN COMPILATION
    // ═══════════════════════════════════

    public function compileJoins(array $joins): string
    {
        if (empty($joins)) return '';

        $parts = [];
        foreach ($joins as $join) {
            $type = $join['type'];
            $table = $this->wrapTable($join['table']);

            if ($type === 'CROSS') {
                $parts[] = " CROSS JOIN {$table}";
            } else {
                $col1 = $this->wrapColumn($join['col1']);
                $col2 = $this->wrapColumn($join['col2']);
                $parts[] = " {$type} JOIN {$table} ON {$col1} {$join['operator']} {$col2}";
            }
        }

        return implode('', $parts);
    }

    // ═══════════════════════════════════
    //  GROUP BY / HAVING
    // ═══════════════════════════════════

    public function compileGroupBy(array $groups): string
    {
        if (empty($groups)) return '';
        $cols = implode(', ', array_map(fn($c) => $this->wrapColumn($c), $groups));
        return " GROUP BY {$cols}";
    }

    public function compileHaving(array $havings): string
    {
        if (empty($havings)) return '';

        $parts = [];
        foreach ($havings as $i => $having) {
            $boolean = $i === 0 ? '' : ' AND ';
            $col = $this->wrapColumn($having['column']);
            $parts[] = $boolean . "{$col} {$having['operator']} {$having['value']}";
        }

        return ' HAVING ' . implode('', $parts);
    }

    // ═══════════════════════════════════
    //  ORDER BY / LIMIT
    // ═══════════════════════════════════

    public function compileOrderBy(array $orders): string
    {
        if (empty($orders)) return '';

        $parts = array_map(
            fn($o) => "{$this->wrapColumn($o['column'])} {$o['direction']}",
            $orders
        );

        return ' ORDER BY ' . implode(', ', $parts);
    }

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

    // ═══════════════════════════════════
    //  DDL
    // ═══════════════════════════════════

    public function compileCreateTable(string $table): string
    {
        return "CREATE TABLE {$this->wrapTable($table)}";
    }

    public function compileDropTable(string $table): string
    {
        return "DROP TABLE {$this->wrapTable($table)}";
    }

    // ═══════════════════════════════════
    //  AGGREGATES
    // ═══════════════════════════════════

    public function compileCount(string $table): string
    {
        return "SELECT COUNT(*) as count FROM {$this->wrapTable($table)}";
    }

    // ═══════════════════════════════════
    //  VALUE QUOTING (for non-binding grammars)
    // ═══════════════════════════════════

    public function quoteValue(mixed $value): string
    {
        if (is_null($value)) return 'NULL';
        if (is_int($value) || is_float($value)) return (string) $value;
        if (is_bool($value)) return $value ? '1' : '0';

        $escaped = str_replace("'", "''", (string) $value);
        return "'{$escaped}'";
    }
}
