<?php

Route::group(['middleware' => ['auth', 'authorize']], function () {

// Routes related to Authentication (password reset, etc)
// Auth::routes();
    Route::namespace('Admin')->prefix('admin')->group(function () {
        Route::resource('about', 'AboutController');
        Route::resource('groups', 'GroupController')->only(['index', 'edit', 'show']);
        Route::resource('preferences', 'PreferenceController');
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
        Route::resource('categories', 'ProcessCategoryController')->parameters([
            'categories' => 'processCategory'
        ]);
    });

    Route::resource('processes', 'ProcessController');
    // Ensure our modeler loads at a distinct url
    Route::get('modeler/{process}', 'Process\ModelerController')->name('modeler');


    Route::resource('profile', 'ProfileController')->only([
        'index', 'edit', 'show'
    ]);
    Route::resource('requests', 'RequestController')->only([
        'index', 'show'
    ]);
    Route::resource('tasks', 'TaskController');

    $this->get('/', 'HomeController@index')->name('home');
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
