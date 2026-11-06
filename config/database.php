<?php

use Illuminate\Support\Str;

return [
    'default' => env('DB_CONNECTION', 'sqlsrv'),

    'connections' => [
        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '(local)'),
            'port' => env('DB_PORT') ?: null,
            'database' => env('DB_DATABASE', 'CITAS'),
            'username' => env('DB_USERNAME') ?: null,
            'password' => env('DB_PASSWORD') ?: null,
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'encrypt' => env('DB_ENCRYPT', 'no'),
            'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'yes'),
            'login_timeout' => (int) env('DB_TIMEOUT', 10),
        ],

        'sigh' => [
            'driver' => 'sqlsrv',
            'host' => env('SIGH_DB_HOST'),
            'port' => env('SIGH_DB_PORT'),
            'database' => env('SIGH_DB_DATABASE'),
            'username' => env('SIGH_DB_USERNAME'),
            'password' => env('SIGH_DB_PASSWORD'),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'encrypt' => env('SIGH_DB_ENCRYPT', 'no'),
            'trust_server_certificate' => env('SIGH_DB_TRUST_SERVER_CERTIFICATE', 'yes'),
        ],
    ],

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    'redis' => [
        'client' => env('REDIS_CLIENT', 'phpredis'),
        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug((string) env('APP_NAME', 'laravel')).'-database-'),
        ],
        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],
        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],
    ],
];
