<?php
Route::group(
    [
    'middleware' => ['auth:api', 'bindings', 'authorize'],
    'prefix' => 'api/1.0',
    'namespace' => 'ProcessMaker\Http\Controllers\Api',
    'as' => 'api.',
    ], function() {

    Route::apiResource('users', 'UserController');
    Route::apiResource('groups', 'GroupController');
    Route::apiResource('group_members', 'GroupMemberController')->only(['index', 'show', 'destroy', 'store']);
    Route::apiResource('environment_variables', 'EnvironmentVariablesController');
    Route::apiResource('screens', 'ScreenController');
    Route::apiResource('screen_categories', 'ScreenCategoryController');
    Route::post('scripts/preview', 'ScriptController@preview')->name('script.preview');
    Route::apiResource('scripts', 'ScriptController');
    Route::apiResource('processes', 'ProcessController');
    Route::apiResource('process_categories', 'ProcessCategoryController');
    Route::apiResource('tasks', 'TaskController')->only(['index', 'show', 'update']);
    Route::apiResource('requests', 'ProcessRequestController');
    Route::apiResource('requests.files', 'ProcessRequestFileController');
    Route::post('process_events/{process}', 'ProcessController@triggerStartEvent')->name('process_events.trigger');
    Route::apiResource('files', 'FileController');
    Route::get('notifications', 'NotificationController@index');
    Route::put('notifications', 'NotificationController@update');
    Route::apiResource('task_assignments', 'TaskAssignmentController')->only(['index', 'store', 'update', 'destroy']);
    }
);
