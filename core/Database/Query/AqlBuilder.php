<?php

namespace Lonate\Core\Database\Query;

use Lonate\Core\Database\Contracts\ConnectionInterface;

/**
 * Class AqlBuilder
 * 
 * Fluent query builder that compiles to Agricultural Query Language (AQL)
 * instead of SQL. This is SawitDB's native query language.
 * 
 * AQL Mapping:
 *   LAHAN       → CREATE TABLE
 *   BAKAR LAHAN → DROP TABLE
 *   TANAM KE    → INSERT INTO
 *   PANEN       → SELECT
 *   PUPUK       → UPDATE
 *   GUSUR       → DELETE
 *   DIMANA      → WHERE
 *   URUTKAN     → ORDER BY
 *   HANYA       → LIMIT
 *   GABUNG      → JOIN
 * 
 * Usage:
 *   $aql = new AqlBuilder($connection);
 *   $aql->dari('users')->panen('*')->dimana('name', 'Budi')->dapatkan();
 * 
 * @package Lonate\Core\Database\Query
 */
class AqlBuilder
{
    protected ConnectionInterface $connection;
    protected string $table = '';
    protected array $columns = ['*'];
    protected array $conditions = [];
    protected ?string $orderColumn = null;
    protected ?string $orderDirection = null;
    protected ?int $limitValue = null;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Set the table (kebun/lahan) to query.
     *
     * @param string $table
     * @return static
     */
    public function dari(string $table): static
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Set columns to harvest (SELECT).
     * PANEN name, age DARI users
     *
     * @param string ...$columns
     * @return static
     */
    public function panen(string ...$columns): static
    {
        $this->columns = $columns ?: ['*'];
        return $this;
    }

    /**
     * Add a WHERE condition.
     * DIMANA name = 'Budi'
     *
     * @param string $column
     * @param mixed $operator
     * @param mixed $value
     * @return static
     */
    public function dimana(string $column, mixed $operator = null, mixed $value = null): static
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->conditions[] = compact('column', 'operator', 'value');
        return $this;
    }

    /**
     * Set ordering.
     * URUTKAN BERDASARKAN name NAIK
     *
     * @param string $column
     * @param string $direction 'NAIK' (ASC) or 'TURUN' (DESC)
     * @return static
     */
    public function urutkan(string $column, string $direction = 'NAIK'): static
    {
        $this->orderColumn = $column;
        $this->orderDirection = strtoupper($direction);
        return $this;
    }

    /**
     * Set result limit.
     * HANYA 10
     *
     * @param int $limit
     * @return static
     */
    public function hanya(int $limit): static
    {
        $this->limitValue = $limit;
        return $this;
    }

    // ═══════════════════════════════════
    //  EXECUTION METHODS
    // ═══════════════════════════════════

    /**
     * Execute a SELECT (PANEN) query and return results.
     *
     * @return array
     */
    public function dapatkan(): array
    {
        $aql = $this->compilePanen();
        $this->connection->query($aql);
        return $this->connection->fetch();
    }

    /**
     * Execute a SELECT and return first result.
     *
     * @return array|null
     */
    public function pertama(): ?array
    {
        $this->limitValue = 1;
        $results = $this->dapatkan();
        return $results[0] ?? null;
    }

    /**
     * Insert data (TANAM).
     * TANAM KE users (name) BIBIT ('Budi')
     *
     * @param array $data Associative array of column => value
     * @return mixed
     */
    public function tanam(array $data): mixed
    {
        $columns = implode(', ', array_keys($data));
        $values = implode(', ', array_map(fn($v) => $this->quote($v), array_values($data)));
        
        $aql = "TANAM KE {$this->table} ({$columns}) BIBIT ({$values})";
        $this->connection->query($aql);
        return $this->connection->lastInsertId();
    }

    /**
     * Update data (PUPUK).
     * PUPUK users DENGAN name='NewName' DIMANA id = 1
     *
     * @param array $data Associative array of column => newValue
     * @return mixed
     */
    public function pupuk(array $data): mixed
    {
        $setParts = [];
        foreach ($data as $col => $val) {
            $setParts[] = "{$col}=" . $this->quote($val);
        }
        $setStr = implode(', ', $setParts);

        $aql = "PUPUK {$this->table} DENGAN {$setStr}";

        if (!empty($this->conditions)) {
            $aql .= ' DIMANA ' . $this->compileConditions();
        }

        $this->connection->query($aql);
        return $this->connection->fetch();
    }

    /**
     * Delete data (GUSUR).
     * GUSUR DARI users DIMANA id = 1
     *
     * @return mixed
     */
    public function gusur(): mixed
    {
        $aql = "GUSUR DARI {$this->table}";

        if (!empty($this->conditions)) {
            $aql .= ' DIMANA ' . $this->compileConditions();
        }

        $this->connection->query($aql);
        return $this->connection->fetch();
    }

    /**
     * Create a table (LAHAN).
     *
     * @return mixed
     */
    public function lahan(): mixed
    {
        $aql = "LAHAN {$this->table}";
        $this->connection->query($aql);
        return $this->connection->fetch();
    }

    /**
     * Drop a table (BAKAR LAHAN).
     *
     * @return mixed
     */
    public function bakar(): mixed
    {
        $aql = "BAKAR LAHAN {$this->table}";
        $this->connection->query($aql);
        return $this->connection->fetch();
    }

    // ═══════════════════════════════════
    //  COMPILATION
    // ═══════════════════════════════════

    /**
     * Compile a PANEN (SELECT) AQL query string.
     *
     * @return string
     */
    protected function compilePanen(): string
    {
        $cols = implode(', ', $this->columns);
        $aql = "PANEN {$cols} DARI {$this->table}";

        if (!empty($this->conditions)) {
            $aql .= ' DIMANA ' . $this->compileConditions();
        }

        if ($this->orderColumn) {
            $aql .= " URUTKAN BERDASARKAN {$this->orderColumn} {$this->orderDirection}";
        }

        if ($this->limitValue !== null) {
            $aql .= " HANYA {$this->limitValue}";
        }

        return $aql;
    }

    /**
     * Compile WHERE conditions to AQL DIMANA clause.
     *
     * @return string
     */
    protected function compileConditions(): string
    {
        $parts = [];
        foreach ($this->conditions as $cond) {
            $parts[] = "{$cond['column']} {$cond['operator']} " . $this->quote($cond['value']);
        }
        return implode(' AND ', $parts);
    }

    /**
     * Quote a value for AQL.
     *
     * @param mixed $value
     * @return string
     */
    protected function quote(mixed $value): string
    {
        if (is_null($value)) return 'NULL';
        if (is_int($value) || is_float($value)) return (string) $value;
        if (is_bool($value)) return $value ? '1' : '0';

        $escaped = str_replace("'", "''", (string) $value);
        return "'{$escaped}'";
    }
}
