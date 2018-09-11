<?php

Route::group(
  [
    'middleware' => 'auth:api',
    'prefix' => 'api/1.0',
    'namespace' => 'ProcessMaker\Http\Controllers\Api'
  ], function() {

    Route::resource('groups', 'GroupController');
    Route::resource('forms', 'FormController');
    Route::resource('scripts', 'ScriptController');

  }
);
