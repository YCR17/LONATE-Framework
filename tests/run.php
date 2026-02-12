<?php

require __DIR__ . '/../vendor/autoload.php';

$tests = [
    __DIR__ . '/view_tests.php',
    __DIR__ . '/http_client_tests.php',
    __DIR__ . '/http_client_extended_tests.php'
];

$failed = 0;
foreach ($tests as $t) {
    echo "Running " . basename($t) . "... ";
    $res = include $t;
    if ($res === true) {
        echo "OK\n";
    } else {
        echo "FAILED\n";
        $failed++;
    }
}

if ($failed > 0) {
    echo "Some tests failed: {$failed}\n";
    exit(1);
}

echo "All tests passed.\n";
return 0;
