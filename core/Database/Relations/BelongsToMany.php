<?php

namespace Lonate\Core\Database\Relations;

use Lonate\Core\Database\Model;
use Lonate\Core\Database\Query\Builder;

/**
 * BelongsToMany relationship (many-to-many via pivot table).
 * Example: User belongsToMany Roles (through role_user pivot)
 */
class BelongsToMany extends Relation
{
    protected string $table;
    protected string $foreignPivotKey;
    protected string $relatedPivotKey;

    public function __construct(
        Builder $query,
        Model $parent,
        string $table,
        string $foreignPivotKey,
        string $relatedPivotKey
    ) {
        $this->table = $table;
        $this->foreignPivotKey = $foreignPivotKey;
        $this->relatedPivotKey = $relatedPivotKey;

        parent::__construct($query, $parent, $foreignPivotKey, $parent->getKeyName());
    }

    public function getResults(): array
    {
        $parentKey = $this->parent->getKey();
        if ($parentKey === null) return [];

        // This would require JOIN support in the builder
        // For now, do two queries (pivot lookup + related fetch)
        $app = $this->parent->resolveApp ?? ($GLOBALS['app'] ?? null);
        // Simple implementation: return empty for now, full JOIN in Builder phase
        return [];
    }
}
