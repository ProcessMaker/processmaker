<?php

use ProcessMaker\Http\Controllers\Api\Requests\RequestsController;

Route::group(['middleware' => ['auth', 'sanitize', 'external.connection']], function () {

    // Routes related to Authentication (password reset, etc)
    // Auth::routes();
    Route::namespace('Admin')->prefix('admin')->group(function () {
        Route::get('groups', 'GroupController@index')->name('groups.index')->middleware('can:view-groups');
        // Route::get('groups/{group}', 'GroupController@show')->name('groups.show')->middleware('can:show-groups,group');
        Route::get('groups/{group}/edit', 'GroupController@edit')->name('groups.edit')->middleware('can:edit-groups,group');

        Route::get('users', 'UserController@index')->name('users.index')->middleware('can:view-users');
        Route::get('users/{user}/edit', 'UserController@edit')->name('users.edit')->middleware('can:edit-users,user');

        Route::get('auth-clients', 'AuthClientController@index')->name('auth-clients.index')->middleware('can:view-auth_clients');

        Route::get('customize-ui', 'CssOverrideController@edit')->name('customize-ui.edit');

        Route::get('script-executors', 'ScriptExecutorController@index')->name('script-executors.index');
    });

    Route::get('admin', 'AdminController@index')->name('admin.index');

    Route::namespace('Process')->prefix('designer')->group(function () {
        Route::get('environment-variables', 'EnvironmentVariablesController@index')->name('environment-variables.index')->middleware('can:view-environment_variables');
        Route::get('environment-variables/{environment_variable}/edit', 'EnvironmentVariablesController@edit')->name('environment-variables.edit')->middleware('can:edit-environment_variables,environment_variable ');

        Route::get('screens', 'ScreenController@index')->name('screens.index')->middleware('can:view-screens');
        Route::get('screens/{screen}/edit', 'ScreenController@edit')->name('screens.edit')->middleware('can:edit-screens,screen');
        Route::get('screens/{screen}/export', 'ScreenController@export')->name('screens.export')->middleware('can:export-screens');
        Route::get('screens/import', 'ScreenController@import')->name('screens.import')->middleware('can:import-screens');
        Route::get('screens/{screen}/download/{key}', 'ScreenController@download')->name('screens.download')->middleware('can:export-screens');
        Route::get('screen-builder/{screen}/edit', 'ScreenBuilderController@edit')->name('screen-builder.edit')->middleware('can:edit-screens,screen');

        Route::get('scripts', 'ScriptController@index')->name('scripts.index')->middleware('can:view-scripts');
        Route::get('scripts/{script}/edit', 'ScriptController@edit')->name('scripts.edit')->middleware('can:edit-scripts,script');
        Route::get('scripts/{script}/builder', 'ScriptController@builder')->name('scripts.builder')->middleware('can:edit-scripts,script');
    });

    Route::get('designer/processes/categories', 'ProcessController@index')->name('process-categories.index') ->middleware ('can:view-process-categories');

    Route::get('designer/screens/categories', 'Process\ScreenController@index')->name('screen-categories.index')->middleware ('can:view-screen-categories');

    Route::get('designer/scripts/categories', 'Process\ScriptController@index')->name('script-categories.index') ->middleware('can:view-script-categories');

    Route::get('processes', 'ProcessController@index')->name('processes.index');
    Route::get('processes/{process}/edit', 'ProcessController@edit')->name('processes.edit')->middleware('can:edit-processes');
    Route::get('processes/{process}/export', 'ProcessController@export')->name('processes.export')->middleware('can:export-processes');
    Route::get('processes/import', 'ProcessController@import')->name('processes.import')->middleware('can:import-processes');
    Route::get('processes/{process}/download/{key}', 'ProcessController@download')->name('processes.download')->middleware('can:export-processes');
    Route::get('processes/create', 'ProcessController@create')->name('processes.create')->middleware('can:create-processes');
    Route::post('processes', 'ProcessController@store')->name('processes.store')->middleware('can:edit-processes');
    Route::get('processes/{process}', 'ProcessController@show')->name('processes.show')->middleware('can:view-processes');
    Route::put('processes/{process}', 'ProcessController@update')->name('processes.edit')->middleware('can:edit-processes');
    Route::delete('processes/{process}', 'ProcessController@destroy')->name('processes.destroy')->middleware('can:archive-processes');

    Route::get('about', 'AboutController@index')->name('about.index');

    Route::get('profile/edit', 'ProfileController@edit')->name('profile.edit');
    Route::get('profile/{id}', 'ProfileController@show')->name('profile.show');
    // Ensure our modeler loads at a distinct url
    Route::get('modeler/{process}', 'Process\ModelerController')->name('modeler.show')->middleware('can:edit-processes');

    Route::get('/', 'HomeController@index')->name('home');

    Route::post('/keep-alive', 'Auth\LoginController@keepAlive')->name('keep-alive');

    Route::get('requests/search', 'RequestController@search')->name('requests.search');
    Route::get('requests/{type}', 'RequestController@index')
        ->where('type', 'all|in_progress|completed')
        ->name('requests_by_type');
    Route::get('request/{request}/files/{media}', 'RequestController@downloadFiles')->middleware('can:participate,request');
    Route::get('requests', 'RequestController@index')->name('requests.index');
    Route::get('requests/{request}', 'RequestController@show')->name('requests.show');
    Route::get('requests/{request}/screen/{screen}', 'RequestController@screenPreview')->name('requests.screen-preview');

    Route::get('tasks/search', 'TaskController@search')->name('tasks.search');
    Route::get('tasks', 'TaskController@index')->name('tasks.index');
    Route::get('tasks/{task}/edit', 'TaskController@edit')->name('tasks.edit');

    Route::get('notifications', 'NotificationController@index')->name('notifications.index');

    // Allows for a logged in user to see navigation on a 404 page
    Route::fallback(function () {
        return response()->view('errors.404', [], 404);
    })->name('fallback');
});

// Add our broadcasting routes
Broadcast::routes();

// Authentication Routes...
Route::get('login', 'Auth\LoginController@showLoginForm')->name('login');
Route::post('login', 'Auth\LoginController@loginWithIntendedCheck');
Route::get('logout', 'Auth\LoginController@logout')->name('logout');

// Password Reset Routes...
Route::get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.email');
Route::get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');
Route::post('password/reset', 'Auth\ResetPasswordController@reset');

//overwrite laravel passport
Route::get('oauth/clients', 'Auth\ClientController@index')->name('passport.clients.index')->middleware('can:view-auth_clients');
Route::get('oauth/clients/{client_id}', 'Auth\ClientController@show')->name('passport.clients.show')->middleware('can:view-auth_clients');
Route::post('oauth/clients', 'Auth\ClientController@store')->name('passport.clients.store')->middleware('can:create-auth_clients');
Route::put('oauth/clients/{client_id}', 'Auth\ClientController@update')->name('passport.clients.update')->middleware('can:edit-auth_clients');
Route::delete('oauth/clients/{client_id}', 'Auth\ClientController@destroy')->name('passport.clients.update')->middleware('can:delete-auth_clients');

Route::get('password/success', function () {
    return view('auth.passwords.success', ['title' => __('Password Reset')]);
})->name('password-success');


Route::get('/unavailable', 'UnavailableController@show')->name('error.unavailable');
