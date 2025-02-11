<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enable or Disable ETag Logging
    |--------------------------------------------------------------------------
    |
    | This option allows you to enable or disable the ETag change logging
    | feature.
    |
    */
    'enabled' => env('ETAG_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Log Dynamic Endpoints
    |--------------------------------------------------------------------------
    |
    | Enable or disable logging when an endpoint is detected as dynamic.
    | If set to false, no logs will be recorded for dynamic endpoints.
    |
    */
    'log_dynamic_endpoints' => env('ETAG_LOG_DYNAMIC_ENDPOINTS', false),

    /*
    |--------------------------------------------------------------------------
    | ETag History Limit
    |--------------------------------------------------------------------------
    |
    | The maximum number of ETags to track per endpoint. If the number of
    | unique ETags exceeds this limit, the oldest ETag will be removed.
    |
    */
    'history_limit' => env('ETAG_HISTORY_LIMIT', 10),

    /*
    |--------------------------------------------------------------------------
    | History Cache Expiration Time
    |--------------------------------------------------------------------------
    |
    | The duration (in minutes) for which the ETag history should be stored
    | in the cache. Adjust this based on your caching strategy.
    |
    */
    'history_cache_expiration' => env('ETAG_HISTORY_CACHE_EXPIRATION_MINUTES', 30),
];
