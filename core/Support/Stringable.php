<?php

namespace Lonate\Core\Support;

/**
 * Stringable — fluent string manipulation class.
 * 
 * Usage: str('Hello World')->lower()->replace(' ', '-')->value()
 */
class Stringable
{
    protected string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    // ── Case ──

    public function lower(): static
    {
        return new static(strtolower($this->value));
    }

    public function upper(): static
    {
        return new static(strtoupper($this->value));
    }

    public function title(): static
    {
        return new static(ucwords(strtolower($this->value)));
    }

    public function ucfirst(): static
    {
        return new static(ucfirst($this->value));
    }

    public function camel(): static
    {
        $words = preg_split('/[_\-\s]+/', $this->value);
        $camel = lcfirst(implode('', array_map('ucfirst', $words)));
        return new static($camel);
    }

    public function studly(): static
    {
        $words = preg_split('/[_\-\s]+/', $this->value);
        return new static(implode('', array_map('ucfirst', $words)));
    }

    public function snake(string $delimiter = '_'): static
    {
        $s = preg_replace('/(?<!^)[A-Z]/', $delimiter . '$0', $this->value);
        return new static(strtolower($s));
    }

    public function kebab(): static
    {
        return $this->snake('-');
    }

    // ── Manipulation ──

    public function replace(string|array $search, string|array $replace): static
    {
        return new static(str_replace($search, $replace, $this->value));
    }

    public function replaceFirst(string $search, string $replace): static
    {
        $pos = strpos($this->value, $search);
        if ($pos === false) return new static($this->value);
        return new static(substr_replace($this->value, $replace, $pos, strlen($search)));
    }

    public function replaceLast(string $search, string $replace): static
    {
        $pos = strrpos($this->value, $search);
        if ($pos === false) return new static($this->value);
        return new static(substr_replace($this->value, $replace, $pos, strlen($search)));
    }

    public function trim(string $chars = " \t\n\r\0\x0B"): static
    {
        return new static(trim($this->value, $chars));
    }

    public function ltrim(string $chars = " \t\n\r\0\x0B"): static
    {
        return new static(ltrim($this->value, $chars));
    }

    public function rtrim(string $chars = " \t\n\r\0\x0B"): static
    {
        return new static(rtrim($this->value, $chars));
    }

    public function substr(int $start, ?int $length = null): static
    {
        return new static(substr($this->value, $start, $length ?? strlen($this->value) - $start));
    }

    public function limit(int $limit = 100, string $end = '...'): static
    {
        if (mb_strlen($this->value) <= $limit) return new static($this->value);
        return new static(mb_substr($this->value, 0, $limit) . $end);
    }

    public function slug(string $separator = '-'): static
    {
        $slug = preg_replace('/[^a-zA-Z0-9\s]/', '', $this->value);
        $slug = preg_replace('/\s+/', $separator, strtolower(trim($slug)));
        return new static($slug);
    }

    public function append(string ...$values): static
    {
        return new static($this->value . implode('', $values));
    }

    public function prepend(string ...$values): static
    {
        return new static(implode('', $values) . $this->value);
    }

    public function wrap(string $before, ?string $after = null): static
    {
        return new static($before . $this->value . ($after ?? $before));
    }

    public function repeat(int $times): static
    {
        return new static(str_repeat($this->value, $times));
    }

    public function padLeft(int $length, string $pad = ' '): static
    {
        return new static(str_pad($this->value, $length, $pad, STR_PAD_LEFT));
    }

    public function padRight(int $length, string $pad = ' '): static
    {
        return new static(str_pad($this->value, $length, $pad, STR_PAD_RIGHT));
    }

    public function reverse(): static
    {
        return new static(strrev($this->value));
    }

    // ── Query ──

    public function length(): int
    {
        return mb_strlen($this->value);
    }

    public function isEmpty(): bool
    {
        return $this->value === '';
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    public function contains(string|array $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if (str_contains($this->value, $needle)) return true;
        }
        return false;
    }

    public function startsWith(string|array $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if (str_starts_with($this->value, $needle)) return true;
        }
        return false;
    }

    public function endsWith(string|array $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if (str_ends_with($this->value, $needle)) return true;
        }
        return false;
    }

    public function is(string $pattern): bool
    {
        $pattern = preg_quote($pattern, '#');
        $pattern = str_replace('\*', '.*', $pattern);
        return (bool) preg_match('#^' . $pattern . '$#u', $this->value);
    }

    // ── Split ──

    public function explode(string $delimiter, int $limit = PHP_INT_MAX): array
    {
        return explode($delimiter, $this->value, $limit);
    }

    public function words(int $words = 100, string $end = '...'): static
    {
        $parts = preg_split('/\s+/', $this->value, $words + 1);
        if (count($parts) <= $words) return new static($this->value);
        return new static(implode(' ', array_slice($parts, 0, $words)) . $end);
    }

    // ── Output ──

    public function toString(): string
    {
        return $this->value;
    }

    public function dump(): static
    {
        dump($this->value);
        return $this;
    }

    public function dd(): never
    {
        dd($this->value);
    }

    // ── Conditional ──

    public function when(bool $condition, callable $callback, ?callable $default = null): static
    {
        if ($condition) return $callback($this);
        if ($default) return $default($this);
        return $this;
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
}
