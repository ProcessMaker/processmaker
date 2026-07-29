<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Octane Flush & Warm Lists
    |--------------------------------------------------------------------------
    |
    | 'flush' — Services with mutable state that must be recreated per request.
    |           These singletons will be flushed (re-bound) on each request
    |           automatically while the application runs under Octane.
    |
    | 'warm'  — Services to pre-resolve once when an Octane worker starts,
    |           avoiding lazy-resolution overhead on the first request.
    |
    */

    'flush' => [
        // Services with mutable state that must be recreated per request
        ProcessMaker\Models\AnonymousUser::class,
        ProcessMaker\ImportExport\Extension::class,
        ProcessMaker\ImportExport\SignalHelper::class,
        ProcessMaker\Managers\MenuManager::class,
    ],

    'warm' => [
        ...Laravel\Octane\Octane::defaultServicesToWarm(),

        // Services to pre-resolve on worker start
        ProcessMaker\Managers\PackageManager::class,
        ProcessMaker\Managers\LoginManager::class,
        ProcessMaker\Managers\IndexManager::class,
    ],
];
