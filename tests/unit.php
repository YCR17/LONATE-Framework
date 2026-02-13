<?php

/**
 * LONATE Framework — Comprehensive Verification Test Suite
 * 
 * Tests ALL core components:
 * - Sections 1-10: Use InMemoryDriver (no external DB needed)
 * - Section 11: SawitDB WowoEngine integration tests
 * - Section 12: AQL Builder tests
 * 
 * Run: php tests/test_skeleton.php
 */

$basePath = dirname(__DIR__);

// Force inmemory driver for ORM tests (sections 1-10)
// SawitDB integration tests (11-12) directly instantiate SawitDriver
putenv('DB_CONNECTION=inmemory');
$_ENV['DB_CONNECTION'] = 'inmemory';
$_SERVER['DB_CONNECTION'] = 'inmemory';

// Autoload
require $basePath . '/vendor/autoload.php';

// Track results
$passed = 0;
$failed = 0;
$total = 0;

function test(string $name, callable $fn): void
{
    global $passed, $failed, $total;
    $total++;
    try {
        $result = $fn();
        if ($result === false) {
            throw new Exception("Returned false");
        }
        echo "  \033[32m✓\033[0m {$name}\n";
        $passed++;
    } catch (Throwable $e) {
        echo "  \033[31m✗\033[0m {$name}\n";
        echo "    → \033[31m" . $e->getMessage() . "\033[0m\n";
        $failed++;
    }
}

function assert_equals($expected, $actual, string $msg = ''): void
{
    if ($expected !== $actual) {
        throw new Exception($msg ?: "Expected " . var_export($expected, true) . " got " . var_export($actual, true));
    }
}

function assert_true($value, string $msg = ''): void
{
    if (!$value) {
        throw new Exception($msg ?: "Expected true, got false");
    }
}

function assert_not_null($value, string $msg = ''): void
{
    if ($value === null) {
        throw new Exception($msg ?: "Expected non-null value");
    }
}