<?php

use Illuminate\Support\Facades\Route;
use ProcessMaker\Http\Controllers\Api\V1_1\TaskController;

// Define the prefix and name for version 1.1 of the API routes
Route::prefix('api/1.1')
    ->name('api.1.1.')
    ->group(function () {
        // Tasks Endpoints
        Route::name('tasks.')->group(function () {
            // Route to list tasks
            Route::get('tasks', [TaskController::class, 'index'])
                ->name('index');
        });
    });
