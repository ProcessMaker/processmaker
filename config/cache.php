<?php

use Illuminate\Support\Str;

return [

    'stores' => [
        'global_variables' => [
            'driver' => 'database',
            'table' => 'global_variables',
            'connection' => 'processmaker',
        ],

        'cache_settings' => [
            'driver' => 'redis',
            'connection' => 'cache_settings',
            'lock_connection' => 'cache_settings',
            'prefix' => env('CACHE_SETTING_PREFIX', 'settings:'),
        ],
    ],

];
