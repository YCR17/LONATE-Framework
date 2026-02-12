<?php

namespace Aksa\Database;

use Aksa\Database\DatabaseManager;
use Aksa\Database\QueryBuilder;

abstract class Model
{
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $guarded = [];
    protected $attributes = [];
    protected static $db;
    
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }
    
    public function fill(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->attributes[$key] = $value;
            }
        }
        
        return $this;
    }
    
    protected function isFillable($key)
    {
        if (!empty($this->fillable)) {
            return in_array($key, $this->fillable);
        }
        
        if (!empty($this->guarded)) {
            return !in_array($key, $this->guarded);
        }
        
        return true;
    }
    
    public function __get($key)
    {
        return $this->attributes[$key] ?? null;
    }
    
    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }
    
    public function getAttribute($key)
    {
        return $this->attributes[$key] ?? null;
    }
    
    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
        return $this;
    }
    
    protected function getTable()
    {
        if (isset($this->table)) {
            return $this->table;
        }
        
        $className = (new \ReflectionClass($this))->getShortName();
        return strtolower($className) . 's';
    }
    
    protected static function getDB()
    {
        if (!static::$db) {
            static::$db = DatabaseManager::getInstance();
        }
        return static::$db->getConnection();
    }
    
    public static function all()
    {
        $instance = new static;
        $table = $instance->getTable();
        
        $results = DatabaseManager::getInstance()->table($table)->get();
        
        return array_map(function($row) {
            return new static((array) $row);
        }, $results);
    }
    
    public static function find($id)
    {
        $instance = new static;
        $table = $instance->getTable();
        $primaryKey = $instance->primaryKey;
        
        $result = DatabaseManager::getInstance()->table($table)
            ->where($primaryKey, '=', $id)
            ->first();
        
        return $result ? new static((array) $result) : null;
    }
    
    public static function query()
    {
        $instance = new static;
        $table = $instance->getTable();
        return new QueryBuilder(static::getDB(), $table, static::class);
    }

    public static function where($column, $operator, $value = null)
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        return static::query()->where($column, $operator, $value);
    }

    public static function whereIn($column, array $values)
    {
        return static::query()->whereIn($column, $values);
    }

    public static function orderBy($column, $direction = 'ASC')
    {
        return static::query()->orderBy($column, $direction);
    }

    public static function latest($column = 'created_at')
    {
        return static::query()->orderBy($column, 'DESC');
    }

    public static function firstOrCreate(array $attributes, array $values = [])
    {
        $query = static::query();
        foreach ($attributes as $k => $v) {
            $query->where($k, $v);
        }

        $first = $query->first();

        if ($first) {
            return new static((array) $first);
        }

        $data = array_merge($attributes, $values);
        return static::create($data);
    }
    
    public function save()
    {
        $table = $this->getTable();
        $db = DatabaseManager::getInstance();
        
        if (isset($this->attributes[$this->primaryKey])) {
            // Update
            $id = $this->attributes[$this->primaryKey];
            unset($this->attributes[$this->primaryKey]);
            
            $db->table($table)
                ->where($this->primaryKey, '=', $id)
                ->update($this->attributes);
            
            $this->attributes[$this->primaryKey] = $id;
        } else {
            // Insert
            $id = $db->table($table)->insert($this->attributes);
            $this->attributes[$this->primaryKey] = $id;
        }
        
        return $this;
    }
    
    public function delete()
    {
        if (!isset($this->attributes[$this->primaryKey])) {
            return false;
        }
        
        $table = $this->getTable();
        $id = $this->attributes[$this->primaryKey];
        
        DatabaseManager::getInstance()->table($table)
            ->where($this->primaryKey, '=', $id)
            ->delete();
        
        return true;
    }
    
    public static function create(array $attributes)
    {
        $model = new static($attributes);
        $model->save();
        return $model;
    }
    
    public function update(array $attributes)
    {
        $this->fill($attributes);
        return $this->save();
    }
    
    public function toArray()
    {
        return $this->attributes;
    }
    
    public function toJson()
    {
        return json_encode($this->attributes);
    }
}
