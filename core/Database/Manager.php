<?php

namespace Lonate\Core\Database;

use Lonate\Core\Database\Contracts\ConnectionInterface;
use Lonate\Core\Database\Drivers\MysqlDriver;
use Lonate\Core\Database\Drivers\SawitDriver;
use Lonate\Core\Database\Drivers\InMemoryDriver;
use Lonate\Core\Foundation\Application;

/**
 * Class Manager
 * 
 * Database connection manager. Handles creating and caching
 * database connections by name. Each connection name maps to
 * a driver configuration in config/database.php.
 * 
 * Supports multiple simultaneous connections, enabling models
 * to work across different databases transparently.
 * 
 * @package Lonate\Core\Database
 */
class Manager
{
    protected Application $app;

    /** @var array<string, ConnectionInterface> Cached connection instances */
    protected array $connections = [];

    /** @var array<string, string> Driver name → class mapping */
    protected array $drivers = [
        'mysql'    => MysqlDriver::class,
        'sawit'    => SawitDriver::class,
        'inmemory' => InMemoryDriver::class,
    ];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get a database connection by name.
     * If no name given, returns the default connection.
     *
     * @param string|null $name Connection name from config/database.php
     * @return ConnectionInterface
     * @throws \RuntimeException
     */
    public function connection(?string $name = null): ConnectionInterface
    {
        $config = $this->loadConfig();
        $name = $name ?? $config['default'] ?? 'mysql';

        if (!isset($this->connections[$name])) {
            if (!isset($config['connections'][$name])) {
                throw new \RuntimeException("Database connection [{$name}] is not configured.");
            }
            $this->connections[$name] = $this->makeConnection($config['connections'][$name]);
        }

        return $this->connections[$name];
    }

    /**
     * Create a new connection instance from configuration.
     *
     * @param array $config
     * @return ConnectionInterface
     * @throws \RuntimeException
     */
    protected function makeConnection(array $config): ConnectionInterface
    {
        $driver = $config['driver'] ?? 'mysql';

        if (!isset($this->drivers[$driver])) {
            throw new \RuntimeException("Unsupported database driver [{$driver}].");
        }

        $driverClass = $this->drivers[$driver];
        $connection = new $driverClass();
        $connection->connect($config);

        return $connection;
    }

    /**
     * Register a custom database driver.
     *
     * @param string $name Driver name
     * @param string $class Fully qualified class implementing ConnectionInterface
     * @return void
     */
    public function extend(string $name, string $class): void
    {
        $this->drivers[$name] = $class;
    }

    /**
     * Load the database configuration.
     *
     * @return array
     */
    protected function loadConfig(): array
    {
        return config('database', []);
    }

    /**
     * Disconnect a specific connection.
     *
     * @param string|null $name
     * @return void
     */
    public function disconnect(?string $name = null): void
    {
        $name = $name ?? 'default';
        unset($this->connections[$name]);
    }

    /**
     * Get all active connection names.
     *
     * @return array
     */
    public function getConnections(): array
    {
        return array_keys($this->connections);
    }
}
