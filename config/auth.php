<?php

return [

    'guards' => [
        'api' => [
            'driver' => 'passport',
            'provider' => 'users',
            'hash' => false,
        ],

        'anon' => [
            'driver' => 'anon',
        ],
    ],

    'log_auth_events' => env('LOG_AUTH_EVENTS', true),

];
