<?php

use ProcessMaker\Http\Controllers\Api\Requests\RequestsController;

Route::group(['middleware' => ['auth']], function () {

// Routes related to Authentication (password reset, etc)
// Auth::routes();
    Route::namespace('Admin')->prefix('admin')->group(function () {
        Route::get('groups', 'GroupController@index')->name('groups.index');
        Route::get('groups/{group}', 'GroupController@create')->name('groups.show');
        Route::get('groups/{group}/edit', 'GroupController@create')->name('groups.edit');

        Route::get('users', 'UserController@index')->name('users.index');
        Route::get('users/create', 'UserController@create')->name('users.create');
        Route::get('users/{user}', 'UserController@show')->name('users.show');
        Route::get('users/{user}/edit', 'UserController@edit')->name('users.edit');
    });

    Route::namespace('Process')->prefix('processes')->group(function () {
        Route::get('environment-variables', 'EnvironmentVariablesController@index')->name('environment-variables.index');
        Route::get('environment-variables/{environment-variable}/edit', 'EnvironmentVariablesController@edit')->name('environment-variables.edit');

        Route::get('documents', 'DocumentController@index')->name('documents.index');

        Route::get('screens', 'ScreenController@index')->name('screens.index');
        Route::get('screens/{screen}/edit', 'ScreenController@edit')->name('screens.edit');

        Route::get('screen-builder/{screen}/edit', 'ScreenBuilderController@edit')->name('screen-builder.edit');
        
        Route::get('scripts', 'ScriptController@index')->name('scripts.index');
        Route::get('scripts/{script}/edit', 'ScriptController@edit')->name('scripts.edit');
        Route::get('scripts/{script}/builder', 'ScriptController@builder')->name('scripts.builder');
        
        Route::get('categories', 'ProcessCategoryController@index')->name('categories.index');
        Route::get('categories/{processCategory}/edit', 'ProcessCategoryController@edit')->name('categories.edit');
    });

    Route::get('processes', 'ProcessController@index')->name('processes.index');
    Route::get('processes{process}/edit', 'ProcessController@edit')->name('processes.edit');
    Route::get('processes/create', 'ProcessController@create')->name('processes.create');
    Route::post('processes', 'ProcessController@store')->name('processes.store');
    Route::get('processes/{processes}', 'ProcessController@show')->name('processes.show');
    Route::put('processes/{processes}', 'ProcessController@update')->name('processes.edit');
    Route::delete('processes/{processes}', 'ProcessController@destroy')->name('processes.destroy');

    Route::get('about', 'AboutController@index')->name('about.index');

    Route::get('profile/edit', 'ProfileController@edit')->name('profile.edit');
    Route::get('profile/{id}', 'ProfileController@show');
    Route::put('profile/{id}', 'ProfileController@update')->name('profile.update');
    // Ensure our modeler loads at a distinct url
    Route::get('modeler/{process}', 'Process\ModelerController')->name('modeler.show');

    Route::get('/', 'HomeController@index')->name('home');

    Route::get('requests/{type}', 'RequestController@index')
        ->where('type', 'all|in_progress|completed')
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

