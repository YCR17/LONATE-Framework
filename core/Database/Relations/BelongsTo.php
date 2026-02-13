<?php

namespace Lonate\Core\Database\Relations;

use Lonate\Core\Database\Model;

/**
 * BelongsTo relationship.
 * Example: Post belongsTo User (post has user_id)
 */
class BelongsTo extends Relation
{
    public function getResults(): ?Model
    {
        $foreignKeyValue = $this->parent->getAttribute($this->foreignKey);

        if ($foreignKeyValue === null) return null;

        $result = $this->query->where($this->localKey, $foreignKeyValue)->first();
        if (!$result) return null;

        // Return as raw array since we don't know the related model class here
        return $result;
    }
}
