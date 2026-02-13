<?php

namespace Lonate\Core\Database\Query\Grammars;

/**
 * Class SawitGrammar
 * 
 * SawitDB-specific grammar. Key differences from MySQL:
 * - No backtick quoting (WowoEngine's parser doesn't expect them)
 * - Inline values instead of `?` bindings (WowoEngine handles parsing internally)
 * - Schema-free CREATE TABLE (no column definitions needed)
 * 
 * This grammar is what makes transparent MySQL↔SawitDB switching possible.
 * Users write the same Builder queries, and this Grammar ensures the
 * compiled SQL is compatible with WowoEngine's QueryParser.
 * 
 * @package Lonate\Core\Database\Query\Grammars
 */
class SawitGrammar extends Grammar
{
    protected bool $usesBindings = false;

    /**
     * SawitDB: no wrapping needed.
     */
    public function wrapTable(string $table): string
    {
        return $table;
    }

    /**
     * SawitDB: no wrapping needed.
     */
    public function wrapColumn(string $column): string
    {
        if ($column === '*') return '*';
        return $column;
    }

    /**
     * Inline a value directly into the query string.
     * WowoEngine's QueryParser handles value extraction from the SQL string.
     */
    public function parameter(mixed $value): string
    {
        return $this->quoteValue($value);
    }

    /**
     * SawitDB CREATE TABLE is schema-free — no column definitions.
     */
    public function compileCreateTable(string $table): string
    {
        return "CREATE TABLE {$table}";
    }

    /**
     * SawitDB DROP TABLE syntax.
     */
    public function compileDropTable(string $table): string
    {
        return "DROP TABLE {$table}";
    }
}
