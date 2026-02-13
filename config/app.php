<?php

return [

    'name' => env('APP_NAME', 'LONATE'),

    'env' => env('APP_ENV', 'production'),

    'debug' => (bool) env('APP_DEBUG', false),

    'timezone' => 'Asia/Jakarta',

    'providers' => [

        /*
         * Lonate Framework Service Providers...
         */
        Lonate\Core\Providers\LonateServiceProvider::class,

        /*
         * Application Service Providers...
         */
        App\Providers\AppServiceProvider::class,

    ],

];
