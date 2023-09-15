<?php

use Illuminate\Support\Facades\Route;
use ProcessMaker\Http\Controllers\Api\ProcessController;
use ProcessMaker\Http\Controllers\Api\ProcessRequestController;
use ProcessMaker\Http\Controllers\Api\TaskController;

// Engine
Route::prefix('api/1.0')->name('api.')->group(function () {
    // List of Processes that the user can start
    Route::get('start_processes', [ProcessController::class, 'startProcesses'])->name('processes.start'); // Filtered in controller

    // Start a process
    Route::post('process_events/{process}', [ProcessController::class, 'triggerStartEvent'])->name('process_events.trigger')->middleware('can:start,process');

    // Update task
    Route::put('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update')->middleware('can:update,task');

    // Trigger intermediate event
    Route::post('requests/{request}/events/{event}', [ProcessRequestController::class, 'activateIntermediateEvent'])->name('requests.update,request');
});
