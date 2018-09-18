<?php

Route::group(
  [
    'middleware' => ['auth:api', 'bindings'],
    'prefix' => 'api/1.0',
    'namespace' => 'ProcessMaker\Http\Controllers\Api'
  ], function() {

    Route::apiResource('users', 'UserController');
    Route::apiResource('groups', 'GroupController');
    Route::apiResource('group_members', 'GroupMemberController');
    Route::apiResource('forms', 'FormController');
    Route::apiResource('scripts', 'ScriptController');
    Route::apiResource('processes', 'ProcessController');
    Route::apiResource('process_categories', 'ProcessCategoryController');

    Route::apiResource('requests.tokens', 'ProcessRequestTokenController')->only([
        'index', 'show'
    ]);
  }
);
