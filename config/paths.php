<?php

return [
    'imports' => [
        'path' => 'imports',
    ],

    'keys' => [
        'path' => env('KEYS_PATH', 'keys'),
    ],

    'mailtemplates' => [
        'path' => env('MAILTEMPLATES_PATH', 'mailTemplates'),
    ],

    'process_templates' => [
        'path' => env('PROCESS_TEMPLATES_PATH', 'processes/templates'),
    ],

    'profile' => [
        'path' => 'app/public/profile',
        'url' => env('APP_URL') . '/storage/profile',
    ],

    'settings' => [
        'path' => 'app/public/setting',
        'url' => env('APP_URL') . '/storage/setting',
    ],

    'private_settings' => [
        'path' => 'private/settings',
    ],

    'web_services' => [
        'path' => 'app/private/web_services',
    ],

    'tmp' => [
        'path' => 'app/public/tmp',
        'url' => env('APP_URL') . '/storage/tmp',
    ],

    'samlidp' => [
        'path' => 'samlidp',
    ],

    'decision_tables' => [
        'path' => 'decision-tables',
        'url' => env('APP_URL') . '/storage/decision-tables',
    ],
];
