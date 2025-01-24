<?php

$soketi_settings = [];
if (env('PUSHER_HOST')) {
    $soketi_settings = [
        'host' => env('PUSHER_HOST'),
        'port' => env('PUSHER_PORT', 6001),
        'scheme' => env('PUSHER_SCHEME', 'http'),
        'encrypted' => true,
        'useTLS' => false,
    ];
}

return [

    'connections' => [
        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY', 'app-key'),
            'secret' => env('PUSHER_APP_SECRET', 'app-secret'),
            'app_id' => env('PUSHER_APP_ID', 'app-id'),
            'options' => array_merge([
                'cluster' => env('PUSHER_CLUSTER', 'mt1'),
                'debug' => env('PUSHER_DEBUG', false),
                'useTLS' => env('PUSHER_TLS', true),
            ], $soketi_settings),
        ],
    ],

];
