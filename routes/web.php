<?php

use ProcessMaker\Http\Controllers\Api\Requests\RequestsController;

Route::group(['middleware' => ['auth']], function () {

// Routes related to Authentication (password reset, etc)
// Auth::routes();
    Route::namespace('Admin')->prefix('admin')->group(function () {
        Route::resource('groups', 'GroupController')->only(['index', 'edit']);
        Route::resource('users', 'UserController');
    });

    Route::namespace('Process')->prefix('processes')->group(function () {
        Route::resource('environment-variables', 'EnvironmentVariablesController');
        Route::resource('documents', 'DocumentController');
        Route::resource('screens', 'ScreenController');
        Route::resource('screen-builder', 'ScreenBuilderController')->parameters([
            'screen-builder' => 'screen'
        ])->only(['edit']);
        Route::resource('scripts', 'ScriptController');
        Route::get('scripts/{script}/builder', 'ScriptController@builder');
        Route::resource('categories', 'ProcessCategoryController')->parameters([
            'categories' => 'processCategory'
        ]);
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

