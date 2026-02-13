<?php

namespace Lonate\Core\Database\Relations;

use Lonate\Core\Database\Model;

/**
 * HasOne relationship.
 * Example: User hasOne Phone (phone has user_id)
 */
class HasOne extends Relation
{
    public function getResults(): ?Model
    {
        $fkColumn = $this->getPlainForeignKey();
        $parentKey = $this->parent->getAttribute($this->localKey);

        if ($parentKey === null) return null;

        $result = $this->query->where($fkColumn, $parentKey)->first();
        if (!$result) return null;

        $relatedClass = $this->getRelatedClass();
        return $relatedClass::hydrate($result);
    }

    protected function getPlainForeignKey(): string
    {
        $parts = explode('.', $this->foreignKey);
        return end($parts);
    }

    protected function getRelatedClass(): string
    {
        // Get related model class from the query's table
        // We infer from parent's relationship definition
        return get_class($this->parent);
    }
}
