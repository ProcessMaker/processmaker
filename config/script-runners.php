<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Script Runners
    |--------------------------------------------------------------------------
    |
    | These have been moved to executor packages. See:
    | https://github.com/ProcessMaker/docker-executor-php
    | https://github.com/ProcessMaker/docker-executor-lua
    | https://github.com/ProcessMaker/docker-executor-node
    |
    */
    'php-nayra' => [
        'name' => 'PHP (µService)',
        'runner' => 'PhpRunner',
        'mime_type' => 'application/x-php',
        'options' => ['invokerPackage' => 'ProcessMaker\\Client'],
        'package_path' => base_path('/docker-services/nayra'),
        'init_dockerfile' => [
            'WORKDIR /opt/executor/src',
        ],
        'final_instructions' => [
            'WORKDIR /app',
        ],
        'package_version' => '1.0.0',
        'sdk' => '',
    ],
    'script-microservice' => [
        'base_url' => env('SCRIPT_MICROSERVICE_BASE_URL'),
        'callback' => env('SCRIPT_MICROSERVICE_CALLBACK'),
        'keycloak' => [
            'client_id' => env('KEYCLOAK_CLIENT_ID'),
            'client_secret' => env('KEYCLOAK_CLIENT_SECRET'),
            'redirect' => env('KEYCLOAK_REDIRECT_URI'),
            'base_url' => env('KEYCLOAK_BASE_URL'),
            'username' => env('KEYCLOAK_USERNAME'),
            'password' => env('KEYCLOAK_PASSWORD'),
        ],
    ],
];
