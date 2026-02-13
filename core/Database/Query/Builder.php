<?php

namespace Lonate\Core\Database\Query;

use Lonate\Core\Database\Contracts\ConnectionInterface;
use Lonate\Core\Database\Query\Grammars\Grammar;

/**
 * Class Builder
 * 
 * A fluent query builder that delegates SQL compilation to a Grammar.
 * 
 * This is what makes transparent MySQL↔SawitDB switching possible:
 * the user writes the same Builder API, and the Grammar compiles
 * to the correct SQL dialect for the underlying driver.
 * 
 * Example — this code works identically on MySQL, SawitDB, and InMemory:
 * 
 *   User::where('name', 'Budi')->orderBy('name')->limit(10)->get();
 * 
 * MySQL Grammar:  SELECT `name` FROM `users` WHERE `name` = ? ORDER BY `name` ASC LIMIT 10
 * SawitGrammar:   SELECT name FROM users WHERE name = 'Budi' ORDER BY name ASC LIMIT 10
 * 
 * @package Lonate\Core\Database\Query
 */
class Builder
{
    protected ConnectionInterface $connection;
    protected Grammar $grammar;
    protected string $table = '';
    protected array $wheres = [];
    protected array $bindings = [];
    protected array $columns = ['*'];
    protected array $orders = [];
    protected ?int $limitValue = null;
    protected ?int $offsetValue = null;

    public function __construct(ConnectionInterface $connection, ?Grammar $grammar = null)
    {
        $this->connection = $connection;
        $this->grammar = $grammar ?? $connection->getGrammar();
    }

    /**
     * Set the table for this query.
     */
    public function table(string $table): static
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Set the columns to select.
     */
    public function select(string ...$columns): static
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * Add a WHERE clause.
     * Supports 2-arg (column, value) and 3-arg (column, operator, value).
     */
    public function where(string $column, mixed $operator = null, mixed $value = null): static
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = compact('column', 'operator', 'value');
        $this->bindings[] = $value;
        return $this;
    }

    /**
     * Add an OR WHERE clause.
     */
    public function orWhere(string $column, mixed $operator = null, mixed $value = null): static
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = ['column' => $column, 'operator' => $operator, 'value' => $value, 'boolean' => 'OR'];
        $this->bindings[] = $value;
        return $this;
    }

    /**
     * Add a WHERE NULL clause.
     */
    public function whereNull(string $column): static
    {
        $this->wheres[] = ['column' => $column, 'type' => 'null'];
        return $this;
    }

    /**
     * Add a WHERE NOT NULL clause.
     */
    public function whereNotNull(string $column): static
    {
        $this->wheres[] = ['column' => $column, 'type' => 'not_null'];
        return $this;
    }

    /**
     * Add ORDER BY clause.
     */
    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $this->orders[] = ['column' => $column, 'direction' => strtoupper($direction)];
        return $this;
    }

    /**
     * Set a LIMIT on the query.
     */
    public function limit(int $limit): static
    {
        $this->limitValue = $limit;
        return $this;
    }

    /**
     * Set an OFFSET on the query.
     */
    public function offset(int $offset): static
    {
        $this->offsetValue = $offset;
        return $this;
    }

    // ═══════════════════════════════════
    //  EXECUTION METHODS
    // ═══════════════════════════════════

    /**
     * Insert values into the table.
     */
    public function insert(array $values): bool
    {
        $compiled = $this->grammar->compileInsert($this->table, $values);
        $this->connection->query($compiled['sql'], $compiled['bindings']);
        return true;
    }

    /**
     * Update records matching current WHERE clauses.
     */
    public function update(array $values): int
    {
        $compiled = $this->grammar->compileUpdate($this->table, $values);
        $whereResult = $this->grammar->compileWheres($this->wheres);

        $sql = $compiled['sql'] . $whereResult['sql'];
        $allBindings = array_merge($compiled['bindings'], $whereResult['bindings']);

        $this->connection->query($sql, $allBindings);
        return 1;
    }

    /**
     * Delete records matching current WHERE clauses.
     */
    public function delete(): bool
    {
        $sql = $this->grammar->compileDelete($this->table);
        $whereResult = $this->grammar->compileWheres($this->wheres);

        $this->connection->query($sql . $whereResult['sql'], $whereResult['bindings']);
        return true;
    }

    /**
     * Get all results matching the query.
     */
    public function get(): array
    {
        $sql = $this->grammar->compileSelect($this->table, $this->columns);
        $whereResult = $this->grammar->compileWheres($this->wheres);
        $sql .= $whereResult['sql'];
        $sql .= $this->grammar->compileOrderBy($this->orders);
        $sql .= $this->grammar->compileLimit($this->limitValue, $this->offsetValue);

        $this->connection->query($sql, $whereResult['bindings']);
        return $this->connection->fetch();
    }

    /**
     * Get the first result matching the query.
     */
    public function first(): ?array
    {
        $this->limitValue = 1;
        $results = $this->get();
        return $results[0] ?? null;
    }

    /**
     * Find a record by its primary key.
     */
    public function find(int|string $id, string $primaryKey = 'id'): ?array
    {
        return $this->where($primaryKey, $id)->first();
    }

    /**
     * Count the number of matching records.
     */
    public function count(): int
    {
        $sql = $this->grammar->compileCount($this->table);
        $whereResult = $this->grammar->compileWheres($this->wheres);
        $sql .= $whereResult['sql'];

        $this->connection->query($sql, $whereResult['bindings']);
        $result = $this->connection->fetch();
        return (int) ($result[0]['count'] ?? 0);
    }

    /**
     * Check if any records match the query.
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    // ═══════════════════════════════════
    //  DDL CONVENIENCE METHODS
    // ═══════════════════════════════════

    /**
     * Create a table.
     * MySQL: CREATE TABLE IF NOT EXISTS `table` (`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY) ...
     * SawitDB: CREATE TABLE table (schema-free)
     */
    public function createTable(): mixed
    {
        $sql = $this->grammar->compileCreateTable($this->table);
        return $this->connection->query($sql);
    }

    /**
     * Drop a table.
     */
    public function dropTable(): mixed
    {
        $sql = $this->grammar->compileDropTable($this->table);
        return $this->connection->query($sql);
    }

    // ═══════════════════════════════════
    //  INTROSPECTION
    // ═══════════════════════════════════

    /**
     * Get the Grammar instance being used.
     */
    public function getGrammar(): Grammar
    {
        return $this->grammar;
    }

    /**
     * Compile the current query to SQL (without executing).
     * Useful for debugging.
     */
    public function toSql(): string
    {
        $sql = $this->grammar->compileSelect($this->table, $this->columns);
        $whereResult = $this->grammar->compileWheres($this->wheres);
        $sql .= $whereResult['sql'];
        $sql .= $this->grammar->compileOrderBy($this->orders);
        $sql .= $this->grammar->compileLimit($this->limitValue, $this->offsetValue);

        return $sql;
    }
}
