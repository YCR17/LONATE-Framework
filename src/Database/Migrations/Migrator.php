<?php

namespace Aksa\Database\Migrations;

use Aksa\Database\DatabaseManager;

class Migrator
{
    protected $db;
    protected $migrationsTable = 'migrations';
    protected $path;

    public function __construct()
    {
        $this->db = DatabaseManager::getInstance();
        $this->path = dirname(__DIR__, 2) . '/database/migrations';
        $this->ensureMigrationsTable();

        // Ensure Schema uses same connection
        \Aksa\Database\Schema::setConnection($this->db->getConnection());
    }

    protected function ensureMigrationsTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (id INT AUTO_INCREMENT PRIMARY KEY, migration VARCHAR(255) NOT NULL, batch INT NOT NULL, migrated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)";
        $this->db->exec($sql);
    }

    public function getRan()
    {
        try {
            $stmt = $this->db->query("SELECT migration FROM {$this->migrationsTable}");
            $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);
            $result = [];
            foreach ($rows as $r) $result[] = $r->migration;
            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function run()
    {
        $ran = $this->getRan();
        $files = glob($this->path . '/*.php');
        sort($files);
        $batch = $this->getNextBatchNumber();
        foreach ($files as $file) {
            $name = basename($file, '.php');
            if (in_array($name, $ran)) continue;
            require_once $file;
            $class = $this->classNameFromFile($name);
            if (class_exists($class)) {
                $instance = new $class;
                if (method_exists($instance, 'up')) {
                    // call up() without passing connection; Schema::setConnection used
                    $instance->up();
                    $this->recordMigration($name, $batch);
                    echo "Ran: {$name}\n";
                }
            }
        }
    }

    protected function classNameFromFile($name)
    {
        // remove leading timestamp
        $parts = preg_split('/_/', $name, 4);
        if (count($parts) >= 4) {
            $rest = $parts[3];
        } else {
            $rest = implode('_', array_slice($parts, 1));
        }
        $segments = preg_split('/_/', $rest);
        $segments = array_map('ucfirst', $segments);
        return implode('', $segments);
    }

    protected function recordMigration($name, $batch)
    {
        $stmt = $this->db->prepare("INSERT INTO {$this->migrationsTable} (migration, batch) VALUES (:migration, :batch)");
        $stmt->execute(['migration' => $name, 'batch' => $batch]);
    }

    protected function getNextBatchNumber()
    {
        try {
            $stmt = $this->db->query("SELECT MAX(batch) as max_batch FROM {$this->migrationsTable}");
            $row = $stmt->fetch(\PDO::FETCH_OBJ);
            return ($row && $row->max_batch) ? $row->max_batch + 1 : 1;
        } catch (\Exception $e) {
            return 1;
        }
    }

    public function rollback()
    {
        $stmt = $this->db->query("SELECT migration, batch FROM {$this->migrationsTable} ORDER BY batch DESC, id DESC LIMIT 1");
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        if (!$row) {
            echo "Nothing to rollback.\n";
            return;
        }
        $batch = $row->batch;
        $stmt = $this->db->query("SELECT migration FROM {$this->migrationsTable} WHERE batch = {$batch} ORDER BY id DESC");
        $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);
        foreach ($rows as $r) {
            $name = $r->migration;
            $file = $this->path . '/' . $name . '.php';
            if (file_exists($file)) {
                require_once $file;
                $class = $this->classNameFromFile($name);
                if (class_exists($class)) {
                    $instance = new $class;
                    if (method_exists($instance, 'down')) {
                        // call down() without passing connection; Schema::setConnection used
                        $instance->down();
                        echo "Rolled back: {$name}\n";
                    }
                }
            }
            $this->db->exec("DELETE FROM {$this->migrationsTable} WHERE migration = '{$name}'");
        }
    }
}
