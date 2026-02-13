<?php

namespace Lonate\Core\Database\Drivers;

use Lonate\Core\Database\Contracts\ConnectionInterface;
use Lonate\Core\Database\Query\Grammars\Grammar;
use Lonate\Core\Database\Query\Grammars\MysqlGrammar;
use PDO;
use PDOException;
use PDOStatement;

/**
 * Class MysqlDriver
 * 
 * Production-ready MySQL driver using PDO.
 * 
 * @package Lonate\Core\Database\Drivers
 */
class MysqlDriver implements ConnectionInterface
{
    protected ?PDO $pdo = null;
    protected array $config = [];
    protected ?PDOStatement $lastStatement = null;

    /**
     * Connect to MySQL using PDO.
     *
     * @param array $config
     * @return void
     * @throws PDOException
     */
    public function connect(array $config): void
    {
        $this->config = $config;

        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 3306;
        $database = $config['database'] ?? '';
        $username = $config['username'] ?? 'root';
        $password = $config['password'] ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';
        $collation = $config['collation'] ?? 'utf8mb4_unicode_ci';

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";

        $this->pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '{$charset}' COLLATE '{$collation}'",
        ]);
    }

    /**
     * Execute a query with parameter bindings.
     *
     * @param string $query
     * @param array $bindings
     * @return mixed
     */
    public function query(string $query, array $bindings = []): mixed
    {
        $this->lastStatement = $this->pdo->prepare($query);
        $this->lastStatement->execute($bindings);
        return $this->lastStatement;
    }

    /**
     * Fetch all results from the last query.
     *
     * @return array
     */
    public function fetch(): array
    {
        if (!$this->lastStatement) {
            return [];
        }

        return $this->lastStatement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get the last inserted ID.
     *
     * @return string|int
     */
    public function lastInsertId(): string|int
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Get the underlying PDO instance.
     *
     * @return PDO|null
     */
    public function getPdo(): ?PDO
    {
        return $this->pdo;
    }

    /**
     * Begin a database transaction.
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit a database transaction.
     *
     * @return bool
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Roll back a database transaction.
     *
     * @return bool
     */
    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Get the MySQL query grammar.
     */
    public function getGrammar(): Grammar
    {
        return new MysqlGrammar();
    }
}
