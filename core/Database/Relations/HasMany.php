<?php

namespace Lonate\Core\Database\Relations;

use Lonate\Core\Database\Model;

/**
 * HasMany relationship.
 * Example: User hasMany Posts (post has user_id)
 */
class HasMany extends Relation
{
    public function getResults(): array
    {
        $fkColumn = $this->getPlainForeignKey();
        $parentKey = $this->parent->getAttribute($this->localKey);

        if ($parentKey === null) return [];

        $results = $this->query->where($fkColumn, $parentKey)->get();

        return $results;
    }

    protected function getPlainForeignKey(): string
    {
        $parts = explode('.', $this->foreignKey);
        return end($parts);
    }
}
