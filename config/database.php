<?php

return [
    'default' => env('DB_CONNECTION', 'sawit'),

    'connections' => [
        'mysql' => [
            'driver'   => 'mysql',
            'host'     => env('DB_HOST', '127.0.0.1'),
            'port'     => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'lonate_production'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
        ],

        'sawit' => [
            'driver'   => 'sawit',
            'database' => env('SAWIT_DB_DATABASE', database_path('plantation.sawit')),
        ],

        'inmemory' => [
            'driver' => 'inmemory',
        ],
    ],
];
