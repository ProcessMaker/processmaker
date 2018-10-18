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
    Route::apiResource('forms', 'FormController');
    Route::get('scripts/preview', 'ScriptController@preview')->name('script.preview');
    Route::apiResource('scripts', 'ScriptController');
    Route::apiresource('processes', 'ProcessController');
    Route::apiResource('process_categories', 'ProcessCategoryController');
    Route::apiResource('tasks', 'TaskController')->only(['index', 'show', 'update']);
    Route::apiResource('requests', 'ProcessRequestController');
    Route::post('process_events/{process}', 'ProcessController@triggerStartEvent')->name('process_events.trigger');
    Route::apiResource('files', 'FileController');
    }
);
