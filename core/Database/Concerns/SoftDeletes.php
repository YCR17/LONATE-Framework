<?php

namespace Lonate\Core\Database\Concerns;

/**
 * SoftDeletes trait.
 * 
 * Use this trait in a Model to enable soft deleting.
 * Instead of removing the record, sets a `deleted_at` timestamp.
 * 
 * Usage:
 *   class Post extends Model {
 *       use SoftDeletes;
 *   }
 */
trait SoftDeletes
{
    /**
     * Run the soft delete on the model.
     */
    protected function runSoftDelete(): void
    {
        $time = $this->freshTimestampString();
        $this->attributes[$this->getDeletedAtColumn()] = $time;

        $this->newQuery()
             ->where($this->primaryKey, $this->getKey())
             ->update([$this->getDeletedAtColumn() => $time]);
    }

    /**
     * Restore a soft-deleted model.
     */
    public function restore(): bool
    {
        $this->attributes[$this->getDeletedAtColumn()] = null;

        $this->newQuery()
             ->where($this->primaryKey, $this->getKey())
             ->update([$this->getDeletedAtColumn() => null]);

        $this->exists = true;
        return true;
    }

    /**
     * Determine if the model has been soft deleted.
     */
    public function trashed(): bool
    {
        return !empty($this->attributes[$this->getDeletedAtColumn()]);
    }

    /**
     * Get the deleted_at column name.
     */
    public function getDeletedAtColumn(): string
    {
        return defined(static::class . '::DELETED_AT') ? static::DELETED_AT : 'deleted_at';
    }
}
