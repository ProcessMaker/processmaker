<?php
Route::group(
    [
    'middleware' => ['auth:api', 'bindings'],
    'prefix' => 'api/1.0',
    'namespace' => 'ProcessMaker\Http\Controllers\Api'
    ], function() {

    Route::apiResource('users', 'UserController');
    Route::apiResource('groups', 'GroupController');
    Route::apiResource('group_members', 'GroupMemberController')->only(['index', 'show', 'destroy', 'store']);
    Route::apiResource('environment_variables', 'EnvironmentVariablesController');
    Route::apiResource('forms', 'FormController');
    Route::get('scripts/preview', 'ScriptController@preview')->name('script.preview'); ;
    Route::apiResource('scripts', 'ScriptController');
    Route::apiResource('processes', 'ProcessController');
    Route::apiResource('process_categories', 'ProcessCategoryController');
    Route::apiResource('tasks', 'TaskController')->only(['index', 'show', 'update']);
    Route::apiResource('requests', 'ProcessRequestController');
    Route::post('processes/{process}/events/{event}/trigger', 'ProcessController@triggerStartEvent')->name('process_event');

}
);
