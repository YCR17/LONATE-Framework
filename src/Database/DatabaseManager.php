<?php

namespace MiniLaravel\Database;

class DatabaseManager
{
    protected static $instance;
    protected $connection;
    protected $config;
    
    protected function __construct()
    {
        $this->loadConfig();
        $this->connect();
    }
    
    protected function loadConfig()
    {
        $configFile = dirname(__DIR__, 2) . '/config/database.php';
        
        if (file_exists($configFile)) {
            $this->config = require $configFile;
        } else {
            // Default config
            $this->config = [
                'driver' => 'mysql',
                'host' => 'localhost',
                'database' => 'minilaravel',
                'username' => 'root',
                'password' => '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
            ];
        }
    }
    
    protected function connect()
    {
        $driver = $this->config['driver'];
        $host = $this->config['host'];
        $database = $this->config['database'];
        $username = $this->config['username'];
        $password = $this->config['password'];
        $charset = $this->config['charset'] ?? 'utf8mb4';
        
        try {
            $dsn = "{$driver}:host={$host};dbname={$database};charset={$charset}";
            
            $this->connection = new \PDO($dsn, $username, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (\PDOException $e) {
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance()
    {
        if (!static::$instance) {
            static::$instance = new static();
        }
        
        return static::$instance;
    }
    
    public function getConnection()
    {
        return $this->connection;
    }
    
    public function table($table)
    {
        return new QueryBuilder($this->connection, $table);
    }
    
    public function prepare($sql)
    {
        return $this->connection->prepare($sql);
    }
    
    public function query($sql)
    {
        return $this->connection->query($sql);
    }
    
    public function exec($sql)
    {
        return $this->connection->exec($sql);
    }
    
    public function lastInsertId()
    {
        return $this->connection->lastInsertId();
    }
}
