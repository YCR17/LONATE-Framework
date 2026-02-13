<?php

namespace Lonate\Core\Support;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use ArrayIterator;
use Traversable;

/**
 * Collection class — Laravel-style fluent array wrapper.
 * 
 * Usage: collect([1, 2, 3])->map(fn($n) => $n * 2)->toArray()
 */
class Collection implements ArrayAccess, Countable, IteratorAggregate
{
    protected array $items = [];

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    // =============================
    // Transformations
    // =============================

    public function map(callable $callback): static
    {
        return new static(array_map($callback, $this->items, array_keys($this->items)));
    }

    public function filter(?callable $callback = null): static
    {
        return new static($callback
            ? array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH)
            : array_filter($this->items));
    }

    public function reject(callable $callback): static
    {
        return $this->filter(fn($item, $key) => !$callback($item, $key));
    }

    public function each(callable $callback): static
    {
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key) === false) break;
        }
        return $this;
    }

    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->items, $callback, $initial);
    }

    public function flatMap(callable $callback): static
    {
        return $this->map($callback)->flatten(1);
    }

    public function flatten(int $depth = INF): static
    {
        $result = [];
        $flatten = function ($items, $currentDepth) use (&$result, &$flatten, $depth) {
            foreach ($items as $item) {
                if (is_array($item) && $currentDepth < $depth) {
                    $flatten($item, $currentDepth + 1);
                } else {
                    $result[] = $item;
                }
            }
        };
        $flatten($this->items, 0);
        return new static($result);
    }

    public function chunk(int $size): static
    {
        return new static(array_chunk($this->items, $size));
    }

    public function collapse(): static
    {
        $result = [];
        foreach ($this->items as $item) {
            if (is_array($item)) {
                $result = array_merge($result, $item);
            } else {
                $result[] = $item;
            }
        }
        return new static($result);
    }

    // =============================
    // Lookups & Aggregates
    // =============================

    public function pluck(string $valueKey, ?string $indexKey = null): static
    {
        $result = [];
        foreach ($this->items as $item) {
            $item = (array) $item;
            $value = $item[$valueKey] ?? null;
            if ($indexKey !== null) {
                $result[$item[$indexKey] ?? null] = $value;
            } else {
                $result[] = $value;
            }
        }
        return new static($result);
    }

    public function where(string $key, mixed $operator = null, mixed $value = null): static
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        return $this->filter(function ($item) use ($key, $operator, $value) {
            $item = (array) $item;
            $actual = $item[$key] ?? null;
            return match ($operator) {
                '=' => $actual == $value,
                '===' => $actual === $value,
                '!=' => $actual != $value,
                '>' => $actual > $value,
                '<' => $actual < $value,
                '>=' => $actual >= $value,
                '<=' => $actual <= $value,
                default => $actual == $value,
            };
        });
    }

    public function whereIn(string $key, array $values): static
    {
        return $this->filter(fn($item) => in_array(((array) $item)[$key] ?? null, $values));
    }

    public function first(?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback) {
            foreach ($this->items as $key => $item) {
                if ($callback($item, $key)) return $item;
            }
            return $default;
        }
        return $this->items[array_key_first($this->items)] ?? $default;
    }

    public function last(?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback) {
            $result = $default;
            foreach ($this->items as $key => $item) {
                if ($callback($item, $key)) $result = $item;
            }
            return $result;
        }
        return $this->items[array_key_last($this->items)] ?? $default;
    }

    public function contains(mixed $key, mixed $operator = null, mixed $value = null): bool
    {
        if (is_callable($key)) {
            foreach ($this->items as $k => $item) {
                if ($key($item, $k)) return true;
            }
            return false;
        }

        if (func_num_args() === 1) {
            return in_array($key, $this->items);
        }

        if (func_num_args() === 2) {
            return $this->where($key, $operator)->isNotEmpty();
        }

        return $this->where($key, $operator, $value)->isNotEmpty();
    }

    public function search(mixed $value, bool $strict = false): int|string|false
    {
        return array_search($value, $this->items, $strict);
    }

    // =============================
    // Sorting
    // =============================

    public function sortBy(string|callable $keyOrCallback, int $options = SORT_REGULAR, bool $descending = false): static
    {
        $items = $this->items;
        if (is_callable($keyOrCallback)) {
            usort($items, function ($a, $b) use ($keyOrCallback, $descending) {
                $va = $keyOrCallback($a);
                $vb = $keyOrCallback($b);
                return $descending ? ($vb <=> $va) : ($va <=> $vb);
            });
        } else {
            usort($items, function ($a, $b) use ($keyOrCallback, $descending) {
                $va = ((array) $a)[$keyOrCallback] ?? null;
                $vb = ((array) $b)[$keyOrCallback] ?? null;
                return $descending ? ($vb <=> $va) : ($va <=> $vb);
            });
        }
        return new static($items);
    }

    public function sortByDesc(string|callable $keyOrCallback): static
    {
        return $this->sortBy($keyOrCallback, SORT_REGULAR, true);
    }

    public function sort(?callable $callback = null): static
    {
        $items = $this->items;
        $callback ? usort($items, $callback) : sort($items);
        return new static($items);
    }

    public function reverse(): static
    {
        return new static(array_reverse($this->items, true));
    }

    // =============================
    // Grouping & Unique
    // =============================

    public function groupBy(string|callable $groupBy): static
    {
        $result = [];
        foreach ($this->items as $key => $item) {
            if (is_callable($groupBy)) {
                $groupKey = $groupBy($item, $key);
            } else {
                $groupKey = ((array) $item)[$groupBy] ?? null;
            }
            $result[$groupKey][] = $item;
        }
        return new static(array_map(fn($group) => new static($group), $result));
    }

    public function unique(?string $key = null): static
    {
        if ($key === null) {
            return new static(array_unique($this->items));
        }

        $seen = [];
        $result = [];
        foreach ($this->items as $item) {
            $val = ((array) $item)[$key] ?? null;
            if (!in_array($val, $seen, true)) {
                $seen[] = $val;
                $result[] = $item;
            }
        }
        return new static($result);
    }

    // =============================
    // Math
    // =============================

    public function sum(string|callable|null $callback = null): int|float
    {
        if ($callback === null) return array_sum($this->items);

        $sum = 0;
        foreach ($this->items as $item) {
            $sum += is_callable($callback)
                ? $callback($item)
                : ((array) $item)[$callback] ?? 0;
        }
        return $sum;
    }

    public function avg(string|callable|null $callback = null): int|float|null
    {
        $count = $this->count();
        if ($count === 0) return null;
        return $this->sum($callback) / $count;
    }

    public function min(string|callable|null $callback = null): mixed
    {
        if ($callback === null) return min($this->items);
        return $this->map(fn($item) => is_callable($callback) ? $callback($item) : ((array) $item)[$callback] ?? null)
            ->filter()->sort()->first();
    }

    public function max(string|callable|null $callback = null): mixed
    {
        if ($callback === null) return max($this->items);
        return $this->map(fn($item) => is_callable($callback) ? $callback($item) : ((array) $item)[$callback] ?? null)
            ->filter()->sortByDesc(fn($v) => $v)->first();
    }

    public function median(string|callable|null $callback = null): int|float|null
    {
        $values = $callback
            ? $this->map(fn($i) => is_callable($callback) ? $callback($i) : ((array)$i)[$callback] ?? 0)->sort()->values()->toArray()
            : $this->sort()->values()->toArray();

        $count = count($values);
        if ($count === 0) return null;

        $mid = intdiv($count, 2);
        return $count % 2 === 0
            ? ($values[$mid - 1] + $values[$mid]) / 2
            : $values[$mid];
    }

    // =============================
    // Keys & Values
    // =============================

    public function keys(): static
    {
        return new static(array_keys($this->items));
    }

    public function values(): static
    {
        return new static(array_values($this->items));
    }

    public function flip(): static
    {
        return new static(array_flip($this->items));
    }

    public function merge(array|self $items): static
    {
        $other = $items instanceof self ? $items->toArray() : $items;
        return new static(array_merge($this->items, $other));
    }

    public function combine(array|self $values): static
    {
        $vals = $values instanceof self ? $values->toArray() : $values;
        return new static(array_combine($this->items, $vals));
    }

    public function only(array $keys): static
    {
        return new static(array_intersect_key($this->items, array_flip($keys)));
    }

    public function except(array $keys): static
    {
        return new static(array_diff_key($this->items, array_flip($keys)));
    }

    public function slice(int $offset, ?int $length = null): static
    {
        return new static(array_slice($this->items, $offset, $length, true));
    }

    public function take(int $limit): static
    {
        if ($limit < 0) {
            return new static(array_slice($this->items, $limit));
        }
        return new static(array_slice($this->items, 0, $limit));
    }

    public function skip(int $count): static
    {
        return new static(array_slice($this->items, $count));
    }

    public function push(mixed ...$values): static
    {
        foreach ($values as $value) {
            $this->items[] = $value;
        }
        return $this;
    }

    public function put(string|int $key, mixed $value): static
    {
        $this->items[$key] = $value;
        return $this;
    }

    public function pull(string|int $key, mixed $default = null): mixed
    {
        $value = $this->items[$key] ?? $default;
        unset($this->items[$key]);
        return $value;
    }

    public function forget(string|int|array $keys): static
    {
        foreach ((array) $keys as $key) {
            unset($this->items[$key]);
        }
        return $this;
    }

    public function pop(): mixed
    {
        return array_pop($this->items);
    }

    public function shift(): mixed
    {
        return array_shift($this->items);
    }

    public function prepend(mixed $value, string|int|null $key = null): static
    {
        if ($key !== null) {
            $this->items = [$key => $value] + $this->items;
        } else {
            array_unshift($this->items, $value);
        }
        return $this;
    }

    // =============================
    // Conditionals
    // =============================

    public function when(mixed $value, callable $callback, ?callable $default = null): static
    {
        $val = is_callable($value) ? $value($this) : $value;
        if ($val) {
            return $callback($this, $val);
        } elseif ($default) {
            return $default($this, $val);
        }
        return $this;
    }

    public function unless(mixed $value, callable $callback, ?callable $default = null): static
    {
        return $this->when(!value($value), $callback, $default);
    }

    public function pipe(callable $callback): mixed
    {
        return $callback($this);
    }

    public function tap(callable $callback): static
    {
        $callback($this);
        return $this;
    }

    // =============================
    // String
    // =============================

    public function implode(string $glue, ?string $key = null): string
    {
        if ($key !== null) {
            return implode($glue, $this->pluck($key)->toArray());
        }
        return implode($glue, $this->items);
    }

    public function join(string $glue, string $finalGlue = ''): string
    {
        if ($finalGlue === '' || $this->count() <= 1) {
            return $this->implode($glue);
        }
        $last = $this->pop();
        return $this->implode($glue) . $finalGlue . $last;
    }

    // =============================
    // Status
    // =============================

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    public function count(): int
    {
        return count($this->items);
    }

    // =============================
    // Output
    // =============================

    public function toArray(): array
    {
        return array_map(fn($item) => $item instanceof self ? $item->toArray() : $item, $this->items);
    }

    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options | JSON_UNESCAPED_UNICODE);
    }

    public function all(): array
    {
        return $this->items;
    }

    public function __toString(): string
    {
        return $this->toJson();
    }

    public function dd(): never
    {
        dd($this->toArray());
    }

    public function dump(): static
    {
        dump($this->toArray());
        return $this;
    }

    // =============================
    // ArrayAccess
    // =============================

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    // =============================
    // IteratorAggregate
    // =============================

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
