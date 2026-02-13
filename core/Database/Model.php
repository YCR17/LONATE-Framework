<?php

namespace Lonate\Core\Database;

use Lonate\Core\Database\Query\Builder;
use Lonate\Core\Support\Facade;

/**
 * Class Model
 * 
 * Base ORM class (Eloquent-style Active Record pattern).
 * 
 * Models represent database tables and provide a fluent API for
 * querying and manipulating data. Each model can be tied to a
 * specific database connection, enabling multi-database support
 * without changing query logic.
 * 
 * @method static Builder where(string $column, mixed $operator = null, mixed $value = null)
 * @method static Builder select(string ...$columns)
 * @method static Builder orderBy(string $column, string $direction = 'ASC')
 * @method static Builder limit(int $limit)
 * @method static array get()
 * @method static array|null first()
 * 
 * @package Lonate\Core\Database
 */
class Model
{
    /** @var string|null The table associated with the model */
    protected ?string $table = null;

    /** @var string|null The connection name to use */
    protected ?string $connection = null;

    /** @var string The primary key column */
    protected string $primaryKey = 'id';

    /** @var array The model's attributes */
    protected array $attributes = [];

    /** @var array Mass-assignable attributes (empty = allow all) */
    protected array $fillable = [];

    /** @var array Attributes that are NOT mass-assignable */
    protected array $guarded = [];

    /** @var bool Whether this model instance exists in the database */
    public bool $exists = false;

    /**
     * Create a new model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /**
     * Fill the model with an array of attributes, respecting fillable/guarded.
     *
     * @param array $attributes
     * @return static
     */
    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->attributes[$key] = $value;
            }
        }
        return $this;
    }

    /**
     * Determine if the given attribute is mass-assignable.
     *
     * @param string $key
     * @return bool
     */
    protected function isFillable(string $key): bool
    {
        // If guarded contains '*', nothing is fillable
        if (in_array('*', $this->guarded)) {
            return false;
        }

        // If fillable is empty and key is not guarded, allow
        if (empty($this->fillable)) {
            return !in_array($key, $this->guarded);
        }

        return in_array($key, $this->fillable);
    }

    // =============================
    // Static Query Methods
    // =============================

    /**
     * Find a model by its primary key.
     *
     * @param int|string $id
     * @return static|null
     */
    public static function find(int|string $id): ?static
    {
        $instance = new static;
        $result = $instance->newQuery()->find($id, $instance->primaryKey);

        if (!$result) {
            return null;
        }

        return static::hydrate($result);
    }

    /**
     * Create and persist a new model instance.
     *
     * @param array $attributes
     * @return static
     */
    public static function create(array $attributes = []): static
    {
        $instance = new static($attributes);
        $instance->save();
        return $instance;
    }

    /**
     * Get all records from the model's table.
     *
     * @return array
     */
    public static function all(): array
    {
        $results = static::query()->get();
        return array_map(fn($row) => static::hydrate($row), $results);
    }

    // =============================
    // Instance CRUD Methods
    // =============================

    /**
     * Save the model to the database (insert or update).
     *
     * @return bool
     */
    public function save(): bool
    {
        $query = $this->newQuery();

        if ($this->exists) {
            // Update existing record
            $attrs = $this->attributes;
            unset($attrs[$this->primaryKey]); // Don't update the PK
            $query->where($this->primaryKey, $this->attributes[$this->primaryKey])
                  ->update($attrs);
        } else {
            // Insert new record
            $query->insert($this->attributes);
            
            // Get the auto-incremented ID
            $app = Facade::getFacadeApplication();
            if ($app) {
                $manager = $app->make(Manager::class);
                $connection = $manager->connection($this->connection);
                $lastId = $connection->lastInsertId();
                if ($lastId) {
                    $this->attributes[$this->primaryKey] = $lastId;
                }
            }
            
            $this->exists = true;
        }

        return true;
    }

    /**
     * Delete the model from the database.
     *
     * @return bool
     */
    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        $this->newQuery()
             ->where($this->primaryKey, $this->attributes[$this->primaryKey])
             ->delete();

        $this->exists = false;
        return true;
    }

    /**
     * Update the model with the given attributes and save.
     *
     * @param array $attributes
     * @return bool
     */
    public function update(array $attributes): bool
    {
        $this->fill($attributes);
        return $this->save();
    }

    // =============================
    // Query Builder Factory
    // =============================

    /**
     * Get a new query builder for this model instance.
     *
     * @return Builder
     */
    public function newQuery(): Builder
    {
        return static::query();
    }

    /**
     * Get a new query builder for the model's table and connection.
     *
     * @return Builder
     */
    public static function query(): Builder
    {
        $instance = new static;

        $app = Facade::getFacadeApplication();
        if (!$app) {
            $app = $GLOBALS['app'] ?? null;
        }

        $manager = $app->make(Manager::class);
        $connection = $manager->connection($instance->connection);

        return (new Builder($connection))->table($instance->getTable());
    }

    // =============================
    // Attribute Access
    // =============================

    /**
     * Get an attribute value.
     *
     * @param string $key
     * @return mixed
     */
    public function __get(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Set an attribute value.
     *
     * @param string $key
     * @param mixed $value
     */
    public function __set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Check if an attribute exists.
     *
     * @param string $key
     * @return bool
     */
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Get all attributes as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * JSON serialize the model.
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    // =============================
    // Table Name Convention
    // =============================

    /**
     * Get the table name for this model.
     * Convention: ClassName → lowercase + 's' (e.g., User → users)
     *
     * @return string
     */
    public function getTable(): string
    {
        if ($this->table) {
            return $this->table;
        }

        // Convert class name to table: "BoardResolution" → "boardresolutions"
        $class = basename(str_replace('\\', '/', static::class));
        return strtolower($class) . 's';
    }

    /**
     * Get the primary key column name.
     *
     * @return string
     */
    public function getKeyName(): string
    {
        return $this->primaryKey;
    }

    /**
     * Get the primary key value.
     *
     * @return mixed
     */
    public function getKey(): mixed
    {
        return $this->attributes[$this->primaryKey] ?? null;
    }

    // =============================
    // Hydration
    // =============================

    /**
     * Hydrate a model instance from a database row.
     *
     * @param array $attributes Raw database row
     * @return static
     */
    protected static function hydrate(array $attributes): static
    {
        $instance = new static;
        $instance->attributes = $attributes;
        $instance->exists = true;
        return $instance;
    }

    // =============================
    // Static Proxy
    // =============================

    /**
     * Proxy static calls to a new query builder instance.
     * Enables: Model::where('col', 'val')->get()
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public static function __callStatic(string $method, array $parameters): mixed
    {
        return static::query()->$method(...$parameters);
    }
}
