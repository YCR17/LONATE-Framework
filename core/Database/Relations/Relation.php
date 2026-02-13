<?php

namespace Lonate\Core\Database\Relations;

use Lonate\Core\Database\Model;
use Lonate\Core\Database\Query\Builder;

/**
 * Base Relation class.
 */
abstract class Relation
{
    protected Builder $query;
    protected Model $parent;
    protected string $foreignKey;
    protected string $localKey;

    public function __construct(Builder $query, Model $parent, string $foreignKey, string $localKey)
    {
        $this->query = $query;
        $this->parent = $parent;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
    }

    abstract public function getResults(): mixed;

    public function __call(string $method, array $parameters): mixed
    {
        $result = $this->query->{$method}(...$parameters);
        if ($result === $this->query) {
            return $this;
        }
        return $result;
    }
}
