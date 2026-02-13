<?php

namespace Lonate\Core\Database\Schema;

class Blueprint
{
    protected string $table;
    protected array $columns = [];
    protected array $commands = [];

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function id(): void
    {
        $this->columns[] = "id INT AUTO_INCREMENT PRIMARY KEY";
    }

    public function string(string $column, int $length = 255): self
    {
        $this->columns[] = "$column VARCHAR($length)";
        return $this;
    }
    
    public function text(string $column): self
    {
        $this->columns[] = "$column TEXT";
        return $this;
    }

    public function integer(string $column): self
    {
        $this->columns[] = "$column INT";
        return $this;
    }
    
    public function boolean(string $column): self
    {
        $this->columns[] = "$column TINYINT(1)";
        return $this;
    }
    
    public function timestamps(): void
    {
        $this->columns[] = "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
        $this->columns[] = "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
    }
    
    public function default(mixed $value): self
    {
        // Simple append default value to the last column definition
        // This is a naive implementation for the skeleton
        $lastIdx = count($this->columns) - 1;
        if ($lastIdx >= 0) {
            $val = is_string($value) ? "'$value'" : $value;
            if (is_bool($value)) $val = $value ? 1 : 0;
            $this->columns[$lastIdx] .= " DEFAULT $val";
        }
        return $this;
    }

    public function build(string $connection = null): string
    {
        $columns = implode(', ', $this->columns);
        return "CREATE TABLE IF NOT EXISTS {$this->table} ($columns);";
    }
}
