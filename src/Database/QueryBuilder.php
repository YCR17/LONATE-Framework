<?php

namespace MiniLaravel\Database;

class QueryBuilder
{
    protected $db;
    protected $table;
    protected $modelClass;
    protected $wheres = [];
    protected $limit;
    protected $offset;
    protected $orderBy;
    
    public function __construct($db, $table, $modelClass = null)
    {
        $this->db = $db;
        $this->table = $table;
        $this->modelClass = $modelClass;
    }
    
    public function where($column, $operator, $value = null)
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = compact('column', 'operator', 'value');
        return $this;
    }

    public function whereIn($column, array $values)
    {
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->wheres[] = [
            'raw' => "{$column} IN ({$placeholders})",
            'values' => $values
        ];
        return $this;
    }
    
    public function orderBy($column, $direction = 'ASC')
    {
        $this->orderBy = compact('column', 'direction');
        return $this;
    }
    
    public function limit($limit)
    {
        $this->limit = $limit;
        return $this;
    }
    
    public function offset($offset)
    {
        $this->offset = $offset;
        return $this;
    }
    
    public function get()
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if (!empty($this->wheres)) {
            $sql .= " WHERE ";
            $sql .= $this->buildWhereClause($params);
        }
        
        if ($this->orderBy) {
            $sql .= " ORDER BY {$this->orderBy['column']} {$this->orderBy['direction']}";
        }
        
        if ($this->limit) {
            $sql .= " LIMIT {$this->limit}";
        }
        
        if ($this->offset) {
            $sql .= " OFFSET {$this->offset}";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(\PDO::FETCH_OBJ);
        
        if ($this->modelClass) {
            return array_map(function($row) {
                return new $this->modelClass((array) $row);
            }, $results);
        }
        
        return $results;
    }
    
    public function first()
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    protected function buildWhereClause(& $params)
    {
        $conditions = [];

        foreach ($this->wheres as $where) {
            if (isset($where['raw'])) {
                $conditions[] = $where['raw'];
                foreach ($where['values'] as $v) $params[] = $v;
            } else {
                $conditions[] = "{$where['column']} {$where['operator']} ?";
                $params[] = $where['value'];
            }
        }

        return implode(' AND ', $conditions);
    }
    
    public function count()
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];
        
        if (!empty($this->wheres)) {
            $sql .= " WHERE ";
            $sql .= $this->buildWhereClause($params);
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(\PDO::FETCH_OBJ);
        
        return $result->count;
    }
    
    public function update(array $data)
    {
        $sets = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            $sets[] = "{$key} = ?";
            $params[] = $value;
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets);
        
        if (!empty($this->wheres)) {
            $sql .= " WHERE ";
            $conditions = [];
            
            foreach ($this->wheres as $where) {
                $conditions[] = "{$where['column']} {$where['operator']} ?";
                $params[] = $where['value'];
            }
            
            $sql .= implode(' AND ', $conditions);
        }
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function delete()
    {
        $sql = "DELETE FROM {$this->table}";
        $params = [];
        
        if (!empty($this->wheres)) {
            $sql .= " WHERE ";
            $conditions = [];
            
            foreach ($this->wheres as $where) {
                $conditions[] = "{$where['column']} {$where['operator']} ?";
                $params[] = $where['value'];
            }
            
            $sql .= implode(' AND ', $conditions);
        }
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function insert(array $data)
    {
        $columns = array_keys($data);
        $values = array_values($data);
        $placeholders = array_fill(0, count($values), '?');
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        
        return $this->db->lastInsertId();
    }
}
