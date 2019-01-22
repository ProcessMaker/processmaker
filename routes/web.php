<?php

use ProcessMaker\Http\Controllers\Api\Requests\RequestsController;

Route::group(['middleware' => ['auth']], function () {

// Routes related to Authentication (password reset, etc)
// Auth::routes();
    Route::namespace('Admin')->prefix('admin')->group(function () {
        Route::get('groups', 'GroupController@index')->name('groups.index')->middleware('can:view-groups');
        // Route::get('groups/{group}', 'GroupController@show')->name('groups.show')->middleware('can:show-groups,group');
        Route::get('groups/{group}/edit', 'GroupController@edit')->name('groups.edit')->middleware('can:edit-groups,group');

        Route::get('users', 'UserController@index')->name('users.index')->middleware('can:view-users');
        Route::get('users/create', 'UserController@create')->name('users.create')->middleware('can:create-users');
        Route::get('users/{user}', 'UserController@show')->name('users.show')->middleware('can:view-users, user');
        Route::get('users/{user}/edit', 'UserController@edit')->name('users.edit')->middleware('can:edit-users,user');

        Route::get('auth-clients', 'AuthClientController@index')->name('auth-clients.index')->middleware('can:view-auth-clients');
    });

    Route::get('admin', 'AdminController@dashboard')->name('admin.dashboard');

    Route::namespace('Process')->prefix('processes')->group(function () {
        Route::get('environment-variables', 'EnvironmentVariablesController@index')->name('environment-variables.index')->middleware('can:view-environment_variables');
        Route::get('environment-variables/{environment_variable}/edit', 'EnvironmentVariablesController@edit')->name('environment-variables.edit')->middleware('can:edit-environment_variables,environment_variable ');

        Route::get('screens', 'ScreenController@index')->name('screens.index')->middleware('can:view-screens');
        Route::get('screens/{screen}/edit', 'ScreenController@edit')->name('screens.edit')->middleware('can:edit-screens,screen');
        Route::get('screen-builder/{screen}/edit', 'ScreenBuilderController@edit')->name('screen-builder.edit')->middleware('can:edit-screens,screen');
        
        Route::get('scripts', 'ScriptController@index')->name('scripts.index')->middleware('can:view-scripts');
        Route::get('scripts/{script}/edit', 'ScriptController@edit')->name('scripts.edit')->middleware('can:edit-scripts,script');
        Route::get('scripts/{script}/builder', 'ScriptController@builder')->name('scripts.builder')->middleware('can:edit-scripts,script');
        
        Route::get('categories', 'ProcessCategoryController@index')->name('categories.index')->middleware('can:view-categories');
        Route::get('categories/{processCategory}/edit', 'ProcessCategoryController@edit')->name('categories.edit')->middleware('can:edit-categories,processCategory');
    });

    Route::get('processes/dashboard', 'ProcessController@dashboard')->name('processes.dashboard');
    Route::get('processes', 'ProcessController@index')->name('processes.index')->middleware('can:view-processes');
    Route::get('processes/{process}/edit', 'ProcessController@edit')->name('processes.edit')->middleware('can:edit-processes');
    Route::get('processes/create', 'ProcessController@create')->name('processes.create')->middleware('can:create-processes');
    Route::post('processes', 'ProcessController@store')->name('processes.store')->middleware('can:edit-processes');
    Route::get('processes/{process}', 'ProcessController@show')->name('processes.show')->middleware('can:view-processes');
    Route::put('processes/{process}', 'ProcessController@update')->name('processes.edit')->middleware('can:edit-processes');
    Route::delete('processes/{process}', 'ProcessController@destroy')->name('processes.destroy')->middleware('can:archive-processes');

    Route::get('about', 'AboutController@index')->name('about.index');

    Route::get('profile/edit', 'ProfileController@edit')->name('profile.edit');
    Route::get('profile/{id}', 'ProfileController@show')->name('profile.show');
    Route::put('profile/{id}', 'ProfileController@update')->name('profile.update');
    // Ensure our modeler loads at a distinct url
    Route::get('modeler/{process}', 'Process\ModelerController')->name('modeler.show')->middleware('can:edit-processes');

    Route::get('/', 'HomeController@index')->name('home');

    Route::get('requests/all', 'RequestController@index')->name('requests.all')->middleware('can:view-all_requests');
    Route::get('requests/{type}', 'RequestController@index')
        ->where('type', 'in_progress|completed')
        ->name('requests_by_type');
    Route::get('request/{requestID}/files/{fileID}', 'RequestController@downloadFiles');
    Route::get('requests', 'RequestController@index')->name('requests.index');
    Route::get('requests/{request}', 'RequestController@show')->name('requests.show');


    Route::get('tasks', 'TaskController@index')->name('tasks.index');
    Route::get('tasks/{task}', 'TaskController@show')->name('tasks.show');
    Route::get('tasks/{task}/edit', 'TaskController@edit')->name('tasks.edit');

    Route::get('notifications', 'NotificationController@index')->name('notifications.index');

});

// Add our broadcasting routes
Broadcast::routes();

// Authentication Routes...
$this->get('login', 'Auth\LoginController@showLoginForm')->name('login');
$this->post('login', 'Auth\LoginController@login');
$this->get('logout', 'Auth\LoginController@logout')->name('logout');

// Password Reset Routes...
$this->get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
$this->post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.email');
$this->get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');
$this->post('password/reset', 'Auth\ResetPasswordController@reset');

$this->get('password/success', function () {
    return view('auth.passwords.success', ['title' => __('Password Reset')]);
})->name('password-success');

