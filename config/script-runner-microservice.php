<?php

return [
    'enabled' => env('SCRIPT_MICROSERVICE_ENABLED', true),
    'base_url' => env('SCRIPT_MICROSERVICE_BASE_URL'),
    'callback' => env('SCRIPT_MICROSERVICE_CALLBACK'),
    'version'  => env('SCRIPT_MICROSERVICE_VERSION'),
    'keycloak' => [
        'client_id' => env('KEYCLOAK_CLIENT_ID'),
        'client_secret' => env('KEYCLOAK_CLIENT_SECRET'),
        'redirect' => env('KEYCLOAK_REDIRECT_URI'),
        'base_url' => env('KEYCLOAK_BASE_URL'),
        'username' => env('KEYCLOAK_USERNAME'),
        'password' => env('KEYCLOAK_PASSWORD'),
    ],
    'broadcasting' => [
        'app_id' => env('SCRIPT_MICROSERVICE_PUSHER_APP_ID'),
        'app_key' => env('SCRIPT_MICROSERVICE_PUSHER_APP_KEY'),
        'app_secret' => env('SCRIPT_MICROSERVICE_PUSHER_APP_SECRET'),
        'cluster' => env('SCRIPT_MICROSERVICE_PUSHER_CLUSTER', 'mt1'),
        'scheme' => env('SCRIPT_MICROSERVICE_PUSHER_SCHEME', 'https'),
        'host' => env('SCRIPT_MICROSERVICE_PUSHER_HOST'),
        'port' => env('SCRIPT_MICROSERVICE_PUSHER_PORT', 6001),
    ],
];
