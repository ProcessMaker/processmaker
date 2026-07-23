<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Octane Configuration
    |--------------------------------------------------------------------------
    |
    | This file configures Laravel Octane behavior for ProcessMaker.
    | When OCTANE_ENABLED=true, these settings control how services
    | are managed across requests in long-running workers.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Services to flush on every request
    |--------------------------------------------------------------------------
    |
    | These services will be flushed (removed from the container) after each
    | request so they are re-resolved fresh on the next request.
    | This is critical for services that hold request-scoped state.
    |
    */
    'flush' => [
        // Models with stale data risk
        ProcessMaker\Models\AnonymousUser::class,

        // Import/Export services with mutable state
        ProcessMaker\ImportExport\Extension::class,
        ProcessMaker\ImportExport\SignalHelper::class,

        // Menu manager (holds View::share() state)
        ProcessMaker\Managers\MenuManager::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Services to warm on worker start
    |--------------------------------------------------------------------------
    |
    | These services will be resolved once when Octane starts, so their
    | construction overhead is not paid on the first request.
    |
    */
    'warm' => [
        ProcessMaker\Managers\PackageManager::class,
        ProcessMaker\Managers\LoginManager::class,
        ProcessMaker\Managers\IndexManager::class,
        ProcessMaker\Managers\ModelerManager::class,
        ProcessMaker\Managers\ScreenBuilderManager::class,
        ProcessMaker\Managers\DockerManager::class,
        ProcessMaker\Helpers\PmHash::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Max requests per worker
    |--------------------------------------------------------------------------
    |
    | The number of requests each worker can process before being recycled.
    | This is a safety fallback against potential memory leaks.
    |
    */
    'max_requests' => (int) env('OCTANE_MAX_REQUESTS', 500),
];