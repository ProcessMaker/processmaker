<?php

return [

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'imports' => [
            'driver' => 'local',
            'root' => storage_path('app/imports'),
        ],

        'keys' => [
            'driver' => 'local',
            'root' => env('KEYS_PATH') ? base_path(env('KEYS_PATH')) : storage_path('keys'),
        ],

        'mailtemplates' => [
            'driver' => 'local',
            'root' => env('MAILTEMPLATES_PATH') ? base_path(env('MAILTEMPLATES_PATH')) : storage_path('mailTemplates'),
        ],

        'process_templates' => [
            'driver' => 'local',
            'root' => env('PROCESS_TEMPLATES_PATH') ? base_path(env('PROCESS_TEMPLATES_PATH')) : database_path('processes/templates'),
        ],

        'profile' => [
            'driver' => 'local',
            'root' => storage_path('app/public/profile'),
            'url' => env('APP_URL') . '/storage/profile',
            'visibility' => 'public',
        ],

        'settings' => [
            'driver' => 'local',
            'root' => storage_path('app/public/setting'),
            'url' => env('APP_URL') . '/storage/setting',
            'visibility' => 'public',
        ],

        'private_settings' => [
            'driver' => 'local',
            'root' => storage_path('app/private/settings'),
            'visibility' => 'private',
        ],

        'web_services' => [
            'driver' => 'local',
            'root' => storage_path('app/private/web_services'),
            'visibility' => 'private',
        ],

        'tmp' => [
            'driver' => 'local',
            'root' => storage_path('app/public/tmp'),
            'url' => env('APP_URL') . '/storage/tmp',
            'visibility' => 'public',
        ],

        'samlidp' => [
            'driver' => 'local',
            'root' => storage_path() . '/samlidp',
        ],

        'decision_tables' => [
            'driver' => 'local',
            'root' => storage_path('decision-tables'),
            'url' => env('APP_URL') . '/storage/decision-tables',
            'visibility' => 'private',
        ],
    ],

];
