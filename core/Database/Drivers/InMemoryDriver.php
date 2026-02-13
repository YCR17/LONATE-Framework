<?php

namespace Lonate\Core\Database\Drivers;

use Lonate\Core\Database\Contracts\ConnectionInterface;
use Lonate\Core\Database\Query\Grammars\Grammar;

/**
 * Class InMemoryDriver
 * 
 * In-memory database driver that provides full CRUD operations
 * without requiring any external database engine. Useful for:
 * - Testing without MySQL or SawitDB
 * - Prototyping and demos
 * - Unit tests
 * 
 * Internally stores data as PHP arrays organized by table name.
 * 
 * @package Lonate\Core\Database\Drivers
 */
class InMemoryDriver implements ConnectionInterface
{
    protected array $config = [];
    
    /** @var array<string, array<int, array>> In-memory table storage */
    protected static array $tables = [];
    
    /** @var array<string, int> Auto-increment counters per table */
    protected static array $autoIncrement = [];
    
    /** @var array Last query result set */
    protected array $lastResult = [];
    
    /** @var string|int Last inserted ID */
    protected string|int $lastId = 0;

    public function connect(array $config): void
    {
        $this->config = $config;
    }

    /**
     * Execute a query against the in-memory store.
     * 
     * Parses SQL-like queries and performs operations on the in-memory arrays.
     * Supports: INSERT, SELECT, UPDATE, DELETE, CREATE TABLE, DROP TABLE
     *
     * @param string $query
     * @param array $bindings
     * @return mixed
     */
    public function query(string $query, array $bindings = []): mixed
    {
        $query = trim($query);
        $upper = strtoupper(substr($query, 0, 6));
        
        return match (true) {
            str_starts_with($upper, 'INSERT') => $this->executeInsert($query, $bindings),
            str_starts_with($upper, 'SELECT') => $this->executeSelect($query, $bindings),
            str_starts_with($upper, 'UPDATE') => $this->executeUpdate($query, $bindings),
            str_starts_with($upper, 'DELETE') => $this->executeDelete($query, $bindings),
            str_starts_with($upper, 'CREATE') => $this->executeCreate($query),
            str_starts_with(strtoupper($query), 'DROP') => $this->executeDrop($query),
            default => $this,
        };
    }

    public function fetch(): array
    {
        return $this->lastResult;
    }

    public function lastInsertId(): string|int
    {
        return $this->lastId;
    }

    /**
     * Execute an INSERT query.
     * Format: INSERT INTO table (col1, col2) VALUES (?, ?)
     */
    protected function executeInsert(string $query, array $bindings): mixed
    {
        // Parse: INSERT INTO table (columns) VALUES (?)
        if (preg_match('/INSERT\s+INTO\s+(\w+)\s*\(([^)]+)\)\s*VALUES\s*\(([^)]+)\)/i', $query, $m)) {
            $table = $m[1];
            $columns = array_map('trim', explode(',', $m[2]));
            
            $this->ensureTable($table);
            
            // Auto-increment ID
            if (!isset(self::$autoIncrement[$table])) {
                self::$autoIncrement[$table] = 0;
            }
            self::$autoIncrement[$table]++;
            
            $row = ['id' => self::$autoIncrement[$table]];
            foreach ($columns as $i => $col) {
                $row[$col] = $bindings[$i] ?? null;
            }
            
            self::$tables[$table][] = $row;
            $this->lastId = self::$autoIncrement[$table];
        }
        
        return $this;
    }

    /**
     * Execute a SELECT query.
     * Format: SELECT columns FROM table [WHERE col = ? AND col2 = ?] [ORDER BY col ASC] [LIMIT n]
     */
    protected function executeSelect(string $query, array $bindings): mixed
    {
        // Parse table name
        if (!preg_match('/FROM\s+(\w+)/i', $query, $m)) {
            $this->lastResult = [];
            return $this;
        }
        
        $table = $m[1];
        $this->ensureTable($table);
        $results = self::$tables[$table];
        
        // Parse columns
        $selectAll = true;
        $columns = [];
        if (preg_match('/SELECT\s+(.+?)\s+FROM/i', $query, $colMatch)) {
            $colStr = trim($colMatch[1]);
            if ($colStr !== '*') {
                $selectAll = false;
                $columns = array_map('trim', explode(',', $colStr));
            }
        }
        
        // Parse WHERE conditions
        $results = $this->applyWhereConditions($query, $results, $bindings);
        
        // Parse ORDER BY
        if (preg_match('/ORDER\s+BY\s+(\w+)\s*(ASC|DESC)?/i', $query, $orderMatch)) {
            $orderCol = $orderMatch[1];
            $orderDir = strtoupper($orderMatch[2] ?? 'ASC');
            usort($results, function ($a, $b) use ($orderCol, $orderDir) {
                $va = $a[$orderCol] ?? null;
                $vb = $b[$orderCol] ?? null;
                $cmp = $va <=> $vb;
                return $orderDir === 'DESC' ? -$cmp : $cmp;
            });
        }
        
        // Parse LIMIT
        if (preg_match('/LIMIT\s+(\d+)/i', $query, $limitMatch)) {
            $results = array_slice($results, 0, (int) $limitMatch[1]);
        }
        
        // Column filtering
        if (!$selectAll && !empty($columns)) {
            $results = array_map(function ($row) use ($columns) {
                return array_intersect_key($row, array_flip($columns));
            }, $results);
        }
        
        $this->lastResult = array_values($results);
        return $this;
    }

    /**
     * Execute an UPDATE query.
     * Format: UPDATE table SET col1 = ?, col2 = ? [WHERE ...]
     */
    protected function executeUpdate(string $query, array $bindings): mixed
    {
        if (!preg_match('/UPDATE\s+(\w+)\s+SET\s+(.+?)(?:\s+WHERE\s+(.+))?$/i', $query, $m)) {
            $this->lastResult = [];
            return $this;
        }
        
        $table = $m[1];
        $setClause = $m[2];
        $this->ensureTable($table);
        
        // Parse SET columns
        $setParts = array_map('trim', explode(',', $setClause));
        $setCols = [];
        foreach ($setParts as $part) {
            if (preg_match('/(\w+)\s*=\s*\?/', $part, $sm)) {
                $setCols[] = $sm[1];
            }
        }
        
        // Split bindings: SET values first, WHERE values after
        $setBindings = array_slice($bindings, 0, count($setCols));
        $whereBindings = array_slice($bindings, count($setCols));
        
        $affected = 0;
        foreach (self::$tables[$table] as &$row) {
            $bindingsCopy = $whereBindings; // Fresh copy for each row
            if ($this->matchesWhereString($m[3] ?? '', $row, $bindingsCopy)) {
                foreach ($setCols as $i => $col) {
                    $row[$col] = $setBindings[$i] ?? null;
                }
                $affected++;
            }
        }
        
        $this->lastResult = [['affected' => $affected]];
        return $this;
    }

    /**
     * Execute a DELETE query.
     * Format: DELETE FROM table [WHERE col = ? AND ...]
     */
    protected function executeDelete(string $query, array $bindings): mixed
    {
        if (!preg_match('/DELETE\s+FROM\s+(\w+)(?:\s+WHERE\s+(.+))?$/i', $query, $m)) {
            $this->lastResult = [];
            return $this;
        }
        
        $table = $m[1];
        $this->ensureTable($table);
        
        $before = count(self::$tables[$table]);
        self::$tables[$table] = array_values(array_filter(
            self::$tables[$table],
            fn($row) => !$this->matchesWhereString($m[2] ?? '', $row, $bindings)
        ));
        
        $affected = $before - count(self::$tables[$table]);
        $this->lastResult = [['affected' => $affected]];
        return $this;
    }

    /**
     * Execute a CREATE TABLE statement (initializes in-memory table).
     */
    protected function executeCreate(string $query): mixed
    {
        if (preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?(\w+)/i', $query, $m)) {
            $table = $m[1];
            if (!isset(self::$tables[$table])) {
                self::$tables[$table] = [];
                self::$autoIncrement[$table] = 0;
            }
        }
        
        $this->lastResult = [];
        return $this;
    }

    /**
     * Execute a DROP TABLE statement.
     */
    protected function executeDrop(string $query): mixed
    {
        if (preg_match('/DROP\s+TABLE\s+(?:IF\s+EXISTS\s+)?(\w+)/i', $query, $m)) {
            unset(self::$tables[$m[1]]);
            unset(self::$autoIncrement[$m[1]]);
        }
        
        $this->lastResult = [];
        return $this;
    }

    /**
     * Apply WHERE conditions from the SQL query to the result set.
     */
    protected function applyWhereConditions(string $query, array $rows, array $bindings): array
    {
        if (!preg_match('/WHERE\s+(.+?)(?:\s+ORDER|\s+LIMIT|$)/i', $query, $m)) {
            return $rows;
        }
        
        return array_filter($rows, fn($row) => $this->matchesWhereString($m[1], $row, $bindings));
    }

    /**
     * Check if a row matches a WHERE string like "col1 = ? AND col2 > ?"
     */
    protected function matchesWhereString(string $whereStr, array $row, array &$bindings): bool
    {
        $whereStr = trim($whereStr);
        if (empty($whereStr)) return true;
        
        // Split by AND
        $conditions = preg_split('/\s+AND\s+/i', $whereStr);
        
        foreach ($conditions as $condition) {
            if (preg_match('/(\w+)\s*(=|!=|<>|>|<|>=|<=|LIKE)\s*\?/i', trim($condition), $cm)) {
                $col = $cm[1];
                $op = strtoupper($cm[2]);
                $val = array_shift($bindings);
                $rowVal = $row[$col] ?? null;
                
                $match = match ($op) {
                    '=' => $rowVal == $val,
                    '!=', '<>' => $rowVal != $val,
                    '>' => $rowVal > $val,
                    '<' => $rowVal < $val,
                    '>=' => $rowVal >= $val,
                    '<=' => $rowVal <= $val,
                    'LIKE' => $this->matchLike($rowVal, $val),
                    default => true,
                };
                
                if (!$match) return false;
            }
        }
        
        return true;
    }

    /**
     * Simple LIKE matching with % wildcards.
     */
    protected function matchLike(mixed $value, string $pattern): bool
    {
        $regex = '/^' . str_replace(['%', '_'], ['.*', '.'], preg_quote($pattern, '/')) . '$/i';
        return (bool) preg_match($regex, (string) $value);
    }

    /**
     * Ensure a table exists in the in-memory store.
     */
    protected function ensureTable(string $table): void
    {
        if (!isset(self::$tables[$table])) {
            self::$tables[$table] = [];
            self::$autoIncrement[$table] = 0;
        }
    }

    /**
     * Reset all in-memory data (useful for testing).
     */
    public static function reset(): void
    {
        self::$tables = [];
        self::$autoIncrement = [];
    }

    /**
     * Get the raw in-memory tables (debugging).
     */
    public static function getTables(): array
    {
        return self::$tables;
    }

    /**
     * Get the query grammar for this connection.
     */
    public function getGrammar(): Grammar
    {
        return new Grammar();
    }
}
