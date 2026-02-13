<?php

namespace Lonate\Core\Database\Drivers;

use Lonate\Core\Database\Contracts\ConnectionInterface;
use Lonate\Core\Database\Query\Grammars\Grammar;
use Lonate\Core\Database\Query\Grammars\SawitGrammar;
use SawitDB\Engine\WowoEngine;

/**
 * Class SawitDriver
 * 
 * Database driver that integrates with the real SawitDB engine
 * (wowoengine/sawitdb-php). Stores data in `.sawit` binary files
 * using a 4KB Paged Heap File architecture with B-Tree indexes.
 * 
 * Supports both standard SQL and Agricultural Query Language (AQL):
 *   SQL: SELECT * FROM users WHERE name = 'Budi'
 *   AQL: PANEN * DARI users DIMANA name = 'Budi'
 * 
 * @package Lonate\Core\Database\Drivers
 */
class SawitDriver implements ConnectionInterface
{
    protected array $config = [];
    
    /** @var WowoEngine|null The SawitDB engine instance */
    protected ?WowoEngine $engine = null;
    
    /** @var array Last query result set */
    protected array $lastResult = [];
    
    /** @var string|int Last inserted ID (tracked manually) */
    protected string|int $lastId = 0;
    
    /** @var int Auto-increment counter per connection */
    protected int $autoIncrement = 0;

    /**
     * Connect to a SawitDB file.
     * 
     * Config expects:
     *   'database' => '/path/to/file.sawit'
     *
     * @param array $config
     * @return void
     */
    public function connect(array $config): void
    {
        $this->config = $config;
        $dbPath = $config['database'] ?? '';
        
        if (empty($dbPath)) {
            // Default to database/plantation.sawit
            $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);
            $dbPath = $basePath . '/database/plantation.sawit';
        }
        
        // Ensure directory exists
        $dir = dirname($dbPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $this->engine = new WowoEngine($dbPath);
    }

    /**
     * Execute a query against the SawitDB engine.
     * 
     * Accepts both standard SQL and AQL syntax.
     * The WowoEngine's QueryParser handles both transparently.
     *
     * @param string $query SQL or AQL query string
     * @param array $bindings Parameter bindings (converted to inline values)
     * @return mixed
     */
    public function query(string $query, array $bindings = []): mixed
    {
        if (!$this->engine) {
            throw new \RuntimeException('SawitDB: Not connected. Call connect() first.');
        }

        // Convert parameterized query (col = ?) to inline values for WowoEngine
        if (!empty($bindings)) {
            $query = $this->interpolateBindings($query, $bindings);
        }

        $result = $this->engine->query($query);

        // Handle different result types
        if (is_array($result)) {
            $this->lastResult = $result;
        } elseif (is_string($result)) {
            // INSERT returns "Bibit tertanam.", UPDATE returns string, etc.
            $this->lastResult = [];
            
            // Track auto-increment for INSERT operations
            if (stripos(trim($query), 'INSERT') === 0 || stripos(trim($query), 'TANAM') === 0) {
                $this->autoIncrement++;
                $this->lastId = $this->autoIncrement;
            }
        } else {
            $this->lastResult = [];
        }

        return $this;
    }

    /**
     * Execute a raw AQL query directly (bypasses SQL translation).
     * 
     * Example: $driver->aql("PANEN * DARI users DIMANA name = 'Budi'")
     *
     * @param string $aqlQuery
     * @return mixed Raw result from WowoEngine
     */
    public function aql(string $aqlQuery): mixed
    {
        if (!$this->engine) {
            throw new \RuntimeException('SawitDB: Not connected.');
        }
        
        $result = $this->engine->query($aqlQuery);
        
        if (is_array($result)) {
            $this->lastResult = $result;
        }
        
        return $result;
    }

    /**
     * Fetch results from the last query.
     *
     * @return array
     */
    public function fetch(): array
    {
        return $this->lastResult;
    }

    /**
     * Get the last auto-increment ID.
     *
     * @return string|int
     */
    public function lastInsertId(): string|int
    {
        return $this->lastId;
    }

    /**
     * Get the underlying WowoEngine instance for advanced operations.
     * 
     * Useful for direct access to:
     *   - $engine->createTable()
     *   - $engine->showTables()
     *   - $engine->createIndex()
     *   - $engine->showIndexes()
     *   - $engine->aggregate()
     *
     * @return WowoEngine|null
     */
    public function getEngine(): ?WowoEngine
    {
        return $this->engine;
    }

    /**
     * Replace ? placeholders with actual values for WowoEngine.
     * 
     * WowoEngine uses its own QueryParser and doesn't support PDO-style
     * parameter binding. This method inlines the values safely.
     *
     * @param string $query
     * @param array $bindings
     * @return string
     */
    protected function interpolateBindings(string $query, array $bindings): string
    {
        $index = 0;
        return preg_replace_callback('/\?/', function () use ($bindings, &$index) {
            if (!isset($bindings[$index])) {
                $index++;
                return '?';
            }
            
            $value = $bindings[$index++];
            
            if (is_null($value)) {
                return 'NULL';
            }
            if (is_int($value) || is_float($value)) {
                return (string) $value;
            }
            if (is_bool($value)) {
                return $value ? '1' : '0';
            }
            
            // Escape single quotes for string values
            $escaped = str_replace("'", "''", (string) $value);
            return "'{$escaped}'";
        }, $query);
    }

    /**
     * Create a table in the SawitDB file.
     *
     * @param string $table
     * @return mixed
     */
    public function createTable(string $table): mixed
    {
        return $this->engine?->createTable($table);
    }

    /**
     * Show all tables in the SawitDB file.
     *
     * @return array
     */
    public function showTables(): array
    {
        $result = $this->engine?->showTables();
        return is_array($result) ? $result : [];
    }

    /**
     * Create an index on a table field.
     *
     * @param string $table
     * @param string $field
     * @return mixed
     */
    public function createIndex(string $table, string $field): mixed
    {
        return $this->engine?->createIndex($table, $field);
    }

    /**
     * Reset auto-increment counter (useful for tests).
     *
     * @return void
     */
    public function resetAutoIncrement(): void
    {
        $this->autoIncrement = 0;
        $this->lastId = 0;
    }

    /**
     * Get the SawitDB query grammar.
     *
     * @return Grammar
     */
    public function getGrammar(): Grammar
    {
        return new SawitGrammar();
    }
}

