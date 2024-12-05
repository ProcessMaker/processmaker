<?php

use Illuminate\Support\Facades\Route;
use ProcessMaker\Http\Controllers\Api\V1_1\CaseController;
use ProcessMaker\Http\Controllers\Api\V1_1\ClipboardController;
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
                ->middleware(['bindings', 'can:view,task']);

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
                ->name('all_cases');

            // Route to list all in-progress cases
            Route::get('get_in_progress', [CaseController::class, 'getInProgress'])
                ->name('in_progress');

            // Route to list all completed cases
            Route::get('get_completed', [CaseController::class, 'getCompleted'])
                ->name('completed');

            // Route to get my cases counters
            Route::get('get_my_cases_counters', [CaseController::class, 'getMyCasesCounters'])
                ->name('my_cases_counters');
        });

        // Clipboard Endpoints
        Route::name('clipboard.')->prefix('clipboard')->group(function () {
            // Get clipboard by user
            Route::get('/get_by_user', [ClipboardController::class, 'showByUserId'])
                ->name('user');

            Route::get('/{clipboard}', [ClipboardController::class, 'show'])
                ->name('show');

            Route::post('/create_or_update', [ClipboardController::class, 'createOrUpdateForUser'])
                ->name('clipboard.createOrUpdateForUser');
        });
    });
