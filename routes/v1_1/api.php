<?php

use Illuminate\Support\Facades\Route;
use ProcessMaker\Http\Controllers\Api\V1_1\CaseController;
use ProcessMaker\Http\Controllers\Api\V1_1\TaskController;

// Define the prefix and name for version 1.1 of the API routes
Route::prefix('api/1.1')
    ->name('api.1.1.')
    ->group(function () {
        // Tasks Endpoints
        Route::name('tasks.')->prefix('tasks')->group(function () {
            // Route to list tasks
            Route::get('/', [TaskController::class, 'index'])
                ->name('index');

            // Route to show a task
            Route::get('/{task}', [TaskController::class, 'show'])
                ->name('show')
                ->middleware(['bindings','can:view,task']);

            // Route to show the screen of a task
            Route::get('/{taskId}/screen', [TaskController::class, 'showScreen'])
                ->name('show.screen');

            // Route to show the interstitial screen of a task
            Route::get('/{taskId}/interstitial', [TaskController::class, 'showInterstitial'])
                ->name('show.interstitial');
        });

        // Cases Endpoints
        Route::name('cases.')->prefix('cases')->group(function () {
            // Route to list all cases
            Route::get('get_all_cases', [CaseController::class, 'getAllCases'])
                ->name('cases.all_cases');
        });
    });
