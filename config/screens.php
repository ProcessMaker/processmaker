<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Screen Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Here you can configure the screen caching behavior.
    |
    */

    'cache' => [
        // Cache manager to use: 'new' for ScreenCacheManager, 'legacy' for ScreenCompiledManager
        'manager' => env('SCREEN_CACHE_MANAGER', 'legacy'),

        // Cache driver to use (redis, file)
        'driver' => env('SCREEN_CACHE_DRIVER', 'file'),

        // Default TTL for cached screens (24 hours)
        'ttl' => env('SCREEN_CACHE_TTL', 86400),
    ],
];
