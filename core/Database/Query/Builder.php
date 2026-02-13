<?php

namespace Lonate\Core\Database\Query;

use Lonate\Core\Database\Contracts\ConnectionInterface;
use Lonate\Core\Database\Query\Grammars\Grammar;

/**
 * Class Builder
 * 
 * Fluent query builder with Grammar-based SQL compilation.
 * Supports transparent MySQL↔SawitDB switching.
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
    protected array $groups = [];
    protected array $havings = [];
    protected array $joins = [];
    protected bool $distinctValue = false;

    public function __construct(ConnectionInterface $connection, ?Grammar $grammar = null)
    {
        $this->connection = $connection;
        $this->grammar = $grammar ?? $connection->getGrammar();
    }

    // ═══════════════════════════════════
    //  FLUENT SETTERS
    // ═══════════════════════════════════

    public function table(string $table): static
    {
        $this->table = $table;
        return $this;
    }

    public function select(string ...$columns): static
    {
        $this->columns = $columns;
        return $this;
    }

    public function addSelect(string ...$columns): static
    {
        if ($this->columns === ['*']) {
            $this->columns = $columns;
        } else {
            $this->columns = array_merge($this->columns, $columns);
        }
        return $this;
    }

    public function distinct(): static
    {
        $this->distinctValue = true;
        return $this;
    }

    // ═══════════════════════════════════
    //  WHERE CLAUSES
    // ═══════════════════════════════════

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

    public function whereNull(string $column): static
    {
        $this->wheres[] = ['column' => $column, 'type' => 'null'];
        return $this;
    }

    public function whereNotNull(string $column): static
    {
        $this->wheres[] = ['column' => $column, 'type' => 'not_null'];
        return $this;
    }

    public function whereIn(string $column, array $values): static
    {
        $this->wheres[] = ['column' => $column, 'type' => 'in', 'values' => $values];
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    public function whereNotIn(string $column, array $values): static
    {
        $this->wheres[] = ['column' => $column, 'type' => 'not_in', 'values' => $values];
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    public function whereBetween(string $column, array $range): static
    {
        $this->wheres[] = ['column' => $column, 'type' => 'between', 'values' => $range];
        $this->bindings[] = $range[0];
        $this->bindings[] = $range[1];
        return $this;
    }

    public function whereNotBetween(string $column, array $range): static
    {
        $this->wheres[] = ['column' => $column, 'type' => 'not_between', 'values' => $range];
        $this->bindings[] = $range[0];
        $this->bindings[] = $range[1];
        return $this;
    }

    public function whereLike(string $column, string $value): static
    {
        $this->wheres[] = ['column' => $column, 'operator' => 'LIKE', 'value' => $value];
        $this->bindings[] = $value;
        return $this;
    }

    // ═══════════════════════════════════
    //  ORDER / LIMIT / OFFSET
    // ═══════════════════════════════════

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $this->orders[] = ['column' => $column, 'direction' => strtoupper($direction)];
        return $this;
    }

    public function latest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, 'DESC');
    }

    public function oldest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, 'ASC');
    }

    public function limit(int $limit): static
    {
        $this->limitValue = $limit;
        return $this;
    }

    public function take(int $limit): static
    {
        return $this->limit($limit);
    }

    public function offset(int $offset): static
    {
        $this->offsetValue = $offset;
        return $this;
    }

    public function skip(int $offset): static
    {
        return $this->offset($offset);
    }

    // ═══════════════════════════════════
    //  GROUP BY / HAVING
    // ═══════════════════════════════════

    public function groupBy(string ...$columns): static
    {
        $this->groups = array_merge($this->groups, $columns);
        return $this;
    }

    public function having(string $column, mixed $operator = null, mixed $value = null): static
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        $this->havings[] = compact('column', 'operator', 'value');
        return $this;
    }

    // ═══════════════════════════════════
    //  JOINS
    // ═══════════════════════════════════

    public function join(string $table, string $col1, string $operator, string $col2, string $type = 'INNER'): static
    {
        $this->joins[] = compact('table', 'col1', 'operator', 'col2', 'type');
        return $this;
    }

    public function leftJoin(string $table, string $col1, string $operator, string $col2): static
    {
        return $this->join($table, $col1, $operator, $col2, 'LEFT');
    }

    public function rightJoin(string $table, string $col1, string $operator, string $col2): static
    {
        return $this->join($table, $col1, $operator, $col2, 'RIGHT');
    }

    public function crossJoin(string $table): static
    {
        $this->joins[] = ['table' => $table, 'col1' => '', 'operator' => '', 'col2' => '', 'type' => 'CROSS'];
        return $this;
    }

    // ═══════════════════════════════════
    //  EXECUTION METHODS
    // ═══════════════════════════════════

    public function insert(array $values): bool
    {
        $compiled = $this->grammar->compileInsert($this->table, $values);
        $this->connection->query($compiled['sql'], $compiled['bindings']);
        return true;
    }

    public function update(array $values): int
    {
        $compiled = $this->grammar->compileUpdate($this->table, $values);
        $whereResult = $this->grammar->compileWheres($this->wheres);

        $sql = $compiled['sql'] . $whereResult['sql'];
        $allBindings = array_merge($compiled['bindings'], $whereResult['bindings']);

        $this->connection->query($sql, $allBindings);
        return 1;
    }

    public function delete(): bool
    {
        $sql = $this->grammar->compileDelete($this->table);
        $whereResult = $this->grammar->compileWheres($this->wheres);

        $this->connection->query($sql . $whereResult['sql'], $whereResult['bindings']);
        return true;
    }

    public function increment(string $column, int|float $amount = 1, array $extra = []): int
    {
        $values = array_merge([$column => $this->raw("{$column} + {$amount}")], $extra);
        return $this->update($values);
    }

    public function decrement(string $column, int|float $amount = 1, array $extra = []): int
    {
        $values = array_merge([$column => $this->raw("{$column} - {$amount}")], $extra);
        return $this->update($values);
    }

    // ═══════════════════════════════════
    //  RETRIEVAL METHODS
    // ═══════════════════════════════════

    public function get(): array
    {
        $sql = $this->compileFull();

        $whereResult = $this->grammar->compileWheres($this->wheres);
        $this->connection->query($sql, $whereResult['bindings']);
        return $this->connection->fetch();
    }

    public function first(): ?array
    {
        $this->limitValue = 1;
        $results = $this->get();
        return $results[0] ?? null;
    }

    public function find(int|string $id, string $primaryKey = 'id'): ?array
    {
        return $this->where($primaryKey, $id)->first();
    }

    public function value(string $column): mixed
    {
        $result = $this->select($column)->first();
        return $result[$column] ?? null;
    }

    public function pluck(string $column, ?string $key = null): array
    {
        $results = $this->get();
        $plucked = [];
        foreach ($results as $row) {
            if ($key !== null) {
                $plucked[$row[$key] ?? null] = $row[$column] ?? null;
            } else {
                $plucked[] = $row[$column] ?? null;
            }
        }
        return $plucked;
    }

    public function count(): int
    {
        $sql = $this->grammar->compileCount($this->table);
        $whereResult = $this->grammar->compileWheres($this->wheres);
        $sql .= $whereResult['sql'];

        $this->connection->query($sql, $whereResult['bindings']);
        $result = $this->connection->fetch();
        return (int) ($result[0]['count'] ?? 0);
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    public function doesntExist(): bool
    {
        return !$this->exists();
    }

    public function sum(string $column): int|float
    {
        return $this->aggregate('SUM', $column);
    }

    public function avg(string $column): int|float|null
    {
        return $this->aggregate('AVG', $column);
    }

    public function min(string $column): mixed
    {
        return $this->aggregate('MIN', $column);
    }

    public function max(string $column): mixed
    {
        return $this->aggregate('MAX', $column);
    }

    protected function aggregate(string $function, string $column): mixed
    {
        $col = $this->grammar->wrapColumn($column);
        $alias = strtolower($function);
        $sql = "SELECT {$function}({$col}) AS {$alias} FROM " . $this->grammar->wrapTable($this->table);
        $whereResult = $this->grammar->compileWheres($this->wheres);
        $sql .= $whereResult['sql'];

        $this->connection->query($sql, $whereResult['bindings']);
        $result = $this->connection->fetch();
        return $result[0][$alias] ?? null;
    }

    // ═══════════════════════════════════
    //  CHUNKING / PAGINATION
    // ═══════════════════════════════════

    public function chunk(int $count, callable $callback): bool
    {
        $page = 1;
        do {
            $results = $this->forPage($page, $count)->get();

            if (empty($results)) break;

            if ($callback($results, $page) === false) return false;

            $page++;
        } while (count($results) === $count);

        return true;
    }

    public function forPage(int $page, int $perPage = 15): static
    {
        return $this->offset(($page - 1) * $perPage)->limit($perPage);
    }

    public function paginate(int $perPage = 15, int $page = 1): array
    {
        $total = (clone $this)->count();
        $results = $this->forPage($page, $perPage)->get();

        return [
            'data' => $results,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => (int) ceil($total / $perPage),
            'from' => ($page - 1) * $perPage + 1,
            'to' => min($page * $perPage, $total),
        ];
    }

    // ═══════════════════════════════════
    //  CONDITIONAL
    // ═══════════════════════════════════

    public function when(mixed $value, callable $callback, ?callable $default = null): static
    {
        $val = is_callable($value) ? $value($this) : $value;
        if ($val) {
            $callback($this, $val);
        } elseif ($default) {
            $default($this, $val);
        }
        return $this;
    }

    public function unless(mixed $value, callable $callback, ?callable $default = null): static
    {
        return $this->when(!$value, $callback, $default);
    }

    // ═══════════════════════════════════
    //  DDL CONVENIENCE METHODS
    // ═══════════════════════════════════

    public function createTable(): mixed
    {
        $sql = $this->grammar->compileCreateTable($this->table);
        return $this->connection->query($sql);
    }

    public function dropTable(): mixed
    {
        $sql = $this->grammar->compileDropTable($this->table);
        return $this->connection->query($sql);
    }

    public function truncate(): void
    {
        $this->connection->query("TRUNCATE TABLE " . $this->grammar->wrapTable($this->table));
    }

    // ═══════════════════════════════════
    //  RAW EXPRESSION
    // ═══════════════════════════════════

    public function raw(string $expression): object
    {
        return new class($expression) {
            public function __construct(public string $value) {}
            public function __toString(): string { return $this->value; }
        };
    }

    // ═══════════════════════════════════
    //  INTROSPECTION
    // ═══════════════════════════════════

    public function getGrammar(): Grammar
    {
        return $this->grammar;
    }

    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    public function toSql(): string
    {
        return $this->compileFull();
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }

    // ═══════════════════════════════════
    //  INTERNAL COMPILATION
    // ═══════════════════════════════════

    protected function compileFull(): string
    {
        $sql = $this->grammar->compileSelect($this->table, $this->columns, $this->distinctValue);
        $sql .= $this->grammar->compileJoins($this->joins);

        $whereResult = $this->grammar->compileWheres($this->wheres);
        $sql .= $whereResult['sql'];

        $sql .= $this->grammar->compileGroupBy($this->groups);
        $sql .= $this->grammar->compileHaving($this->havings);
        $sql .= $this->grammar->compileOrderBy($this->orders);
        $sql .= $this->grammar->compileLimit($this->limitValue, $this->offsetValue);

        return $sql;
    }

    /**
     * Clone for subquery usage (e.g. counting total before pagination).
     */
    public function __clone()
    {
        // Deep clone the grammar/connection references are fine
    }
}
