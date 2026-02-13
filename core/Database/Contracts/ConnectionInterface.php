<?php

namespace Lonate\Core\Database\Contracts;

use Lonate\Core\Database\Query\Grammars\Grammar;

interface ConnectionInterface
{
    /**
     * Connect to the database based on config.
     */
    public function connect(array $config): void;

    /**
     * Execute a query against the connection.
     * 
     * @param string $query
     * @param array $bindings
     * @return mixed
     */
    public function query(string $query, array $bindings = []): mixed;

    /**
     * Fetch results from the last query.
     * 
     * @return array
     */
    public function fetch(): array;

    /**
     * Get the last inserted ID.
     * 
     * @return string|int
     */
    public function lastInsertId(): string|int;

    /**
     * Get the query grammar for this connection.
     * 
     * @return Grammar
     */
    public function getGrammar(): Grammar;
}

