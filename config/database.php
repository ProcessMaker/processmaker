<?php

use Illuminate\Support\Str;

return [

    'connections' => [
        'processmaker' => [
            'driver' => env('DB_DRIVER', 'mysql'),
            'host' => env('DB_HOSTNAME', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'processmaker'),
            'username' => env('DB_USERNAME', 'homestead'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => 'InnoDB',
            'timezone'  => env('DB_TIMEZONE'),
        ],

        'data' => [
            'driver' => env('DATA_DB_DRIVER', 'mysql'),
            'host' => env('DATA_DB_HOST', 'localhost'),
            'port' => env('DATA_DB_PORT'),
            'database' => env('DATA_DB_DATABASE'),
            'username' => env('DATA_DB_USERNAME'),
            'password' => env('DATA_DB_PASSWORD'),
            'unix_socket' => env('DATA_DB_SOCKET'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'search_path' => env('DATA_DB_SCHEMA'),
            'engine' => env('DATA_DB_ENGINE'),
            'date_format' => env('DATA_DB_DATE_FORMAT'),
            'timezone'  => env('DATA_DB_TIMEZONE'),
        ],
    ],

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => false, // disable to preserve original behavior for existing applications
    ],

    'upgrades' => 'upgrade_migrations',

    'enable_index_json_columns' => filter_var(env('ENABLE_INDEXED_JSON_COLUMNS', true), FILTER_VALIDATE_BOOLEAN),

];
