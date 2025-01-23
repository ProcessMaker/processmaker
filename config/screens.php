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

        // Cache metrics configuration
        'metrics' => [
            // Enable or disable cache metrics
            'enabled' => env('CACHE_METRICS_ENABLED', true),

            // Maximum number of timing samples to keep per key
            'max_samples' => env('CACHE_METRICS_MAX_SAMPLES', 100),

            // Redis prefix for metrics data
            'redis_prefix' => env('CACHE_METRICS_PREFIX', 'cache:metrics:'),

            // How long to keep metrics data (in seconds)
            'ttl' => env('CACHE_METRICS_TTL', 86400), // 24 hours
        ],
    ],
];
