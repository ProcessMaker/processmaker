<?php

Route::group(
  [
    'middleware' => ['auth:api', 'bindings'],
    'prefix' => 'api/1.0',
    'namespace' => 'ProcessMaker\Http\Controllers\Api'
  ], function() {

    Route::resource('users', 'UserController');
    Route::resource('groups', 'GroupController');
    Route::resource('forms', 'FormController');
    Route::resource('scripts', 'ScriptController');
    Route::resource('processes', 'ProcessController');
    Route::resource('process_categories', 'ProcessCategoryController');
    Route::resource('requests.tokens', 'ProcessRequestTokenController')->except([
        'create', 'store', 'update', 'destroy'
    ]);

  }
);
