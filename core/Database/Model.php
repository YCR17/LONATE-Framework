<?php

namespace Lonate\Core\Database;

use Lonate\Core\Database\Query\Builder;
use Lonate\Core\Support\Facade;

/**
 * Class Model
 * 
 * Eloquent-style Active Record ORM base class.
 * 
 * @method static Builder where(string $column, mixed $operator = null, mixed $value = null)
 * @method static Builder orWhere(string $column, mixed $operator = null, mixed $value = null)
 * @method static Builder whereIn(string $column, array $values)
 * @method static Builder whereNotIn(string $column, array $values)
 * @method static Builder whereBetween(string $column, array $range)
 * @method static Builder whereNull(string $column)
 * @method static Builder whereNotNull(string $column)
 * @method static Builder select(string ...$columns)
 * @method static Builder orderBy(string $column, string $direction = 'ASC')
 * @method static Builder latest(string $column = 'created_at')
 * @method static Builder oldest(string $column = 'created_at')
 * @method static Builder limit(int $limit)
 * @method static Builder offset(int $offset)
 * @method static Builder groupBy(string ...$columns)
 * @method static Builder distinct()
 * @method static array get()
 * @method static array|null first()
 * 
 * @package Lonate\Core\Database
 */
class Model
{
    // =============================
    // Properties
    // =============================

    /** @var string|null The table associated with the model */
    protected ?string $table = null;

    /** @var string|null The connection name to use */
    protected ?string $connection = null;

    /** @var string The primary key column */
    protected string $primaryKey = 'id';

    /** @var bool Whether the primary key is auto-incrementing */
    public bool $incrementing = true;

    /** @var string The primary key type */
    protected string $keyType = 'int';

    /** @var array The model's current attributes */
    protected array $attributes = [];

    /** @var array The model's original attributes (from DB) */
    protected array $original = [];

    /** @var array Mass-assignable attributes (empty = allow all if guarded is empty) */
    protected array $fillable = [];

    /** @var array Attributes that are NOT mass-assignable */
    protected array $guarded = [];

    /** @var bool Whether this model instance exists in the database */
    public bool $exists = false;

    /** @var bool Whether timestamps are auto-managed */
    public bool $timestamps = true;

    /** @var string|null Custom created_at column name */
    const CREATED_AT = 'created_at';

    /** @var string|null Custom updated_at column name */
    const UPDATED_AT = 'updated_at';

    /** @var array Attribute casting definitions */
    protected array $casts = [];

    /** @var array Attributes hidden from serialization */
    protected array $hidden = [];

    /** @var array Attributes visible in serialization (whitelist) */
    protected array $visible = [];

    /** @var array Accessors to append to serialization */
    protected array $appends = [];

    /** @var array Cached relationship results */
    protected array $relations = [];

    // =============================
    // Constructor
    // =============================

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    // =============================
    // Mass Assignment
    // =============================

    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }
        return $this;
    }

    public function forceFill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }
        return $this;
    }

    protected function isFillable(string $key): bool
    {
        if (in_array('*', $this->guarded)) {
            return false;
        }

        if (empty($this->fillable)) {
            return !in_array($key, $this->guarded);
        }

        return in_array($key, $this->fillable);
    }

    // =============================
    // Attribute Access (with Accessors/Mutators/Casts)
    // =============================

    public function getAttribute(string $key): mixed
    {
        // Check for accessor method: getNameAttribute()
        $accessor = 'get' . str_replace('_', '', ucwords($key, '_')) . 'Attribute';
        if (method_exists($this, $accessor)) {
            return $this->{$accessor}($this->attributes[$key] ?? null);
        }

        // Check for relationship method
        if (method_exists($this, $key)) {
            if (!array_key_exists($key, $this->relations)) {
                $this->relations[$key] = $this->{$key}()->getResults();
            }
            return $this->relations[$key];
        }

        $value = $this->attributes[$key] ?? null;

        // Apply cast
        if (isset($this->casts[$key])) {
            return $this->castAttribute($key, $value);
        }

        return $value;
    }

    public function setAttribute(string $key, mixed $value): static
    {
        // Check for mutator method: setNameAttribute()
        $mutator = 'set' . str_replace('_', '', ucwords($key, '_')) . 'Attribute';
        if (method_exists($this, $mutator)) {
            $this->{$mutator}($value);
            return $this;
        }

        $this->attributes[$key] = $value;
        return $this;
    }

    protected function castAttribute(string $key, mixed $value): mixed
    {
        if ($value === null) return null;

        return match ($this->casts[$key]) {
            'int', 'integer' => (int) $value,
            'float', 'double', 'real' => (float) $value,
            'string' => (string) $value,
            'bool', 'boolean' => (bool) $value,
            'array', 'json' => is_string($value) ? json_decode($value, true) : (array) $value,
            'object' => is_string($value) ? json_decode($value) : (object) $value,
            'datetime', 'date' => $value,
            default => $value,
        };
    }

    protected function castAttributeForStorage(string $key, mixed $value): mixed
    {
        if ($value === null) return null;

        return match ($this->casts[$key] ?? null) {
            'array', 'json', 'object' => json_encode($value),
            'bool', 'boolean' => $value ? 1 : 0,
            default => $value,
        };
    }

    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]) || array_key_exists($key, $this->relations);
    }

    public function __unset(string $key): void
    {
        unset($this->attributes[$key], $this->relations[$key]);
    }

    // =============================
    // Dirty Tracking
    // =============================

    public function isDirty(string|array|null $attributes = null): bool
    {
        $dirty = $this->getDirty();

        if ($attributes === null) return count($dirty) > 0;

        foreach ((array) $attributes as $attr) {
            if (array_key_exists($attr, $dirty)) return true;
        }
        return false;
    }

    public function isClean(string|array|null $attributes = null): bool
    {
        return !$this->isDirty($attributes);
    }

    public function getDirty(): array
    {
        $dirty = [];
        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }
        return $dirty;
    }

    public function getOriginal(string $key = null): mixed
    {
        if ($key) return $this->original[$key] ?? null;
        return $this->original;
    }

    public function wasChanged(string|array|null $attributes = null): bool
    {
        return $this->isDirty($attributes);
    }

    // =============================
    // Timestamps
    // =============================

    protected function freshTimestampString(): string
    {
        return date('Y-m-d H:i:s');
    }

    protected function updateTimestamps(): void
    {
        if (!$this->timestamps) return;

        $time = $this->freshTimestampString();

        if (!$this->exists) {
            $this->attributes[static::CREATED_AT] = $time;
        }

        $this->attributes[static::UPDATED_AT] = $time;
    }

    // =============================
    // Static Query Methods
    // =============================

    public static function find(int|string $id): ?static
    {
        $instance = new static;
        $result = $instance->newQuery()->find($id, $instance->primaryKey);

        if (!$result) return null;

        return static::hydrate($result);
    }

    public static function findOrFail(int|string $id): static
    {
        $model = static::find($id);
        if (!$model) {
            throw new \RuntimeException(
                'No query results for model [' . static::class . '] ' . $id
            );
        }
        return $model;
    }

    public static function create(array $attributes = []): static
    {
        $instance = new static($attributes);
        $instance->save();
        return $instance;
    }

    public static function firstOrCreate(array $attributes, array $values = []): static
    {
        $result = static::where(array_key_first($attributes), $attributes[array_key_first($attributes)]);

        // Apply all where conditions
        $keys = array_keys($attributes);
        for ($i = 1; $i < count($keys); $i++) {
            $result = $result->where($keys[$i], $attributes[$keys[$i]]);
        }

        $found = $result->first();
        if ($found) return static::hydrate($found);

        return static::create(array_merge($attributes, $values));
    }

    public static function updateOrCreate(array $attributes, array $values = []): static
    {
        $result = static::where(array_key_first($attributes), $attributes[array_key_first($attributes)]);

        $keys = array_keys($attributes);
        for ($i = 1; $i < count($keys); $i++) {
            $result = $result->where($keys[$i], $attributes[$keys[$i]]);
        }

        $found = $result->first();
        if ($found) {
            $model = static::hydrate($found);
            $model->update($values);
            return $model;
        }

        return static::create(array_merge($attributes, $values));
    }

    public static function destroy(int|string|array ...$ids): int
    {
        $ids = collect(array_flatten($ids));
        $count = 0;

        foreach ($ids as $id) {
            $model = static::find($id);
            if ($model && $model->delete()) {
                $count++;
            }
        }

        return $count;
    }

    public static function all(array $columns = ['*']): array
    {
        $query = static::query();
        if ($columns !== ['*']) {
            $query->select(...$columns);
        }
        $results = $query->get();
        return array_map(fn($row) => static::hydrate($row), $results);
    }

    // =============================
    // Instance CRUD Methods
    // =============================

    public function save(): bool
    {
        $query = $this->newQuery();

        $this->updateTimestamps();

        // Prepare attributes for storage (apply casts)
        $storageAttributes = $this->getStorageAttributes();

        if ($this->exists) {
            $attrs = $storageAttributes;
            unset($attrs[$this->primaryKey]);
            $query->where($this->primaryKey, $this->attributes[$this->primaryKey])
                  ->update($attrs);
        } else {
            $query->insert($storageAttributes);

            // Get the auto-incremented ID
            if ($this->incrementing) {
                $app = $this->resolveApp();
                if ($app) {
                    $manager = $app->make(Manager::class);
                    $connection = $manager->connection($this->connection);
                    $lastId = $connection->lastInsertId();
                    if ($lastId) {
                        $this->attributes[$this->primaryKey] = $lastId;
                    }
                }
            }

            $this->exists = true;
        }

        // Sync original
        $this->syncOriginal();

        return true;
    }

    public function delete(): bool
    {
        if (!$this->exists) return false;

        // Check for SoftDeletes trait
        if (method_exists($this, 'runSoftDelete')) {
            $this->runSoftDelete();
            return true;
        }

        $this->newQuery()
             ->where($this->primaryKey, $this->attributes[$this->primaryKey])
             ->delete();

        $this->exists = false;
        return true;
    }

    public function forceDelete(): bool
    {
        $this->newQuery()
             ->where($this->primaryKey, $this->attributes[$this->primaryKey])
             ->delete();

        $this->exists = false;
        return true;
    }

    public function update(array $attributes): bool
    {
        $this->fill($attributes);
        return $this->save();
    }

    public function fresh(): ?static
    {
        if (!$this->exists) return null;
        return static::find($this->getKey());
    }

    public function refresh(): static
    {
        $fresh = $this->fresh();
        if ($fresh) {
            $this->attributes = $fresh->attributes;
            $this->syncOriginal();
            $this->relations = [];
        }
        return $this;
    }

    public function replicate(array $except = []): static
    {
        $attrs = $this->attributes;
        unset($attrs[$this->primaryKey]);

        foreach ($except as $key) {
            unset($attrs[$key]);
        }

        $instance = new static($attrs);
        return $instance;
    }

    // =============================
    // Query Builder Factory
    // =============================

    public function newQuery(): Builder
    {
        return static::query();
    }

    public static function query(): Builder
    {
        $instance = new static;

        $app = $instance->resolveApp();
        $manager = $app->make(Manager::class);
        $connection = $manager->connection($instance->connection);

        return (new Builder($connection))->table($instance->getTable());
    }

    // =============================
    // Scopes
    // =============================

    /**
     * Proxy static calls to query builder, with scope support.
     */
    public static function __callStatic(string $method, array $parameters): mixed
    {
        $instance = new static;

        // Check for local scope: scopeActive() -> active()
        $scopeMethod = 'scope' . ucfirst($method);
        if (method_exists($instance, $scopeMethod)) {
            $query = static::query();
            $instance->{$scopeMethod}($query, ...$parameters);
            return $query;
        }

        return static::query()->{$method}(...$parameters);
    }

    /**
     * Proxy instance calls to query builder with scope support.
     */
    public function __call(string $method, array $parameters): mixed
    {
        $scopeMethod = 'scope' . ucfirst($method);
        if (method_exists($this, $scopeMethod)) {
            $query = $this->newQuery();
            $this->{$scopeMethod}($query, ...$parameters);
            return $query;
        }

        return $this->newQuery()->{$method}(...$parameters);
    }

    // =============================
    // Serialization
    // =============================

    public function toArray(): array
    {
        $attributes = $this->attributesToArray();

        // Append accessor values
        foreach ($this->appends as $key) {
            $attributes[$key] = $this->getAttribute($key);
        }

        // Include loaded relationships
        foreach ($this->relations as $key => $value) {
            if (is_array($value)) {
                $attributes[$key] = array_map(fn($m) => $m instanceof self ? $m->toArray() : $m, $value);
            } elseif ($value instanceof self) {
                $attributes[$key] = $value->toArray();
            } else {
                $attributes[$key] = $value;
            }
        }

        return $attributes;
    }

    protected function attributesToArray(): array
    {
        $attributes = $this->attributes;

        // Apply casts for output
        foreach ($this->casts as $key => $type) {
            if (array_key_exists($key, $attributes)) {
                $attributes[$key] = $this->castAttribute($key, $attributes[$key]);
            }
        }

        // Apply hidden/visible
        if (!empty($this->visible)) {
            $attributes = array_intersect_key($attributes, array_flip($this->visible));
        }

        if (!empty($this->hidden)) {
            $attributes = array_diff_key($attributes, array_flip($this->hidden));
        }

        return $attributes;
    }

    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options | JSON_UNESCAPED_UNICODE);
    }

    public function __toString(): string
    {
        return $this->toJson();
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    // =============================
    // Relationships
    // =============================

    public function hasOne(string $related, ?string $foreignKey = null, ?string $localKey = null): Relations\HasOne
    {
        $relatedInstance = new $related;
        $foreignKey = $foreignKey ?? $this->getForeignKey();
        $localKey = $localKey ?? $this->primaryKey;

        return new Relations\HasOne(
            $relatedInstance->newQuery(),
            $this,
            $relatedInstance->getTable() . '.' . $foreignKey,
            $localKey
        );
    }

    public function hasMany(string $related, ?string $foreignKey = null, ?string $localKey = null): Relations\HasMany
    {
        $relatedInstance = new $related;
        $foreignKey = $foreignKey ?? $this->getForeignKey();
        $localKey = $localKey ?? $this->primaryKey;

        return new Relations\HasMany(
            $relatedInstance->newQuery(),
            $this,
            $relatedInstance->getTable() . '.' . $foreignKey,
            $localKey
        );
    }

    public function belongsTo(string $related, ?string $foreignKey = null, ?string $ownerKey = null): Relations\BelongsTo
    {
        $relatedInstance = new $related;
        $foreignKey = $foreignKey ?? (strtolower(class_basename($related)) . '_id');
        $ownerKey = $ownerKey ?? $relatedInstance->primaryKey;

        return new Relations\BelongsTo(
            $relatedInstance->newQuery(),
            $this,
            $foreignKey,
            $ownerKey
        );
    }

    public function belongsToMany(
        string $related,
        ?string $table = null,
        ?string $foreignPivotKey = null,
        ?string $relatedPivotKey = null
    ): Relations\BelongsToMany {
        $relatedInstance = new $related;
        $table = $table ?? $this->joiningTable($relatedInstance);
        $foreignPivotKey = $foreignPivotKey ?? $this->getForeignKey();
        $relatedPivotKey = $relatedPivotKey ?? $relatedInstance->getForeignKey();

        return new Relations\BelongsToMany(
            $relatedInstance->newQuery(),
            $this,
            $table,
            $foreignPivotKey,
            $relatedPivotKey
        );
    }

    public function setRelation(string $relation, mixed $value): static
    {
        $this->relations[$relation] = $value;
        return $this;
    }

    // =============================
    // Table Name Convention
    // =============================

    public function getTable(): string
    {
        if ($this->table) return $this->table;

        $class = class_basename(static::class);
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $class)) . 's';
    }

    public function setTable(string $table): static
    {
        $this->table = $table;
        return $this;
    }

    public function getKeyName(): string
    {
        return $this->primaryKey;
    }

    public function getKey(): mixed
    {
        return $this->attributes[$this->primaryKey] ?? null;
    }

    public function getForeignKey(): string
    {
        return strtolower(class_basename(static::class)) . '_id';
    }

    protected function joiningTable(Model $related): string
    {
        $segments = [
            strtolower(class_basename(static::class)),
            strtolower(class_basename(get_class($related))),
        ];
        sort($segments);
        return implode('_', $segments);
    }

    // =============================
    // Hydration & Sync
    // =============================

    public static function hydrate(array $attributes): static
    {
        $instance = new static;
        $instance->attributes = $attributes;
        $instance->exists = true;
        $instance->syncOriginal();
        return $instance;
    }

    public function syncOriginal(): static
    {
        $this->original = $this->attributes;
        return $this;
    }

    protected function getStorageAttributes(): array
    {
        $attrs = $this->attributes;
        foreach ($this->casts as $key => $type) {
            if (array_key_exists($key, $attrs)) {
                $attrs[$key] = $this->castAttributeForStorage($key, $attrs[$key]);
            }
        }
        return $attrs;
    }

    // =============================
    // Helpers
    // =============================

    protected function resolveApp(): mixed
    {
        return Facade::getFacadeApplication() ?? $GLOBALS['app'] ?? null;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getRawAttributes(): array
    {
        return $this->attributes;
    }

    public function getConnection(): ?string
    {
        return $this->connection;
    }

    public function setConnection(?string $name): static
    {
        $this->connection = $name;
        return $this;
    }

    public function getFillable(): array
    {
        return $this->fillable;
    }

    public function getGuarded(): array
    {
        return $this->guarded;
    }

    public function getCasts(): array
    {
        return $this->casts;
    }

    public function is(?Model $model): bool
    {
        return $model !== null
            && $this->getKey() === $model->getKey()
            && $this->getTable() === $model->getTable()
            && $this->getConnection() === $model->getConnection();
    }

    public function isNot(?Model $model): bool
    {
        return !$this->is($model);
    }
}
