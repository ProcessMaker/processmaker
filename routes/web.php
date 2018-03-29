<?php


// Authentication Routes...
$this->get('login', 'Auth\LoginController@showLoginForm')->name('login');
$this->post('login', 'Auth\LoginController@login');
$this->get('logout', 'Auth\LoginController@logout')->name('logout');

// Password Reset Routes...
$this->get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
$this->post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.email');
$this->get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');
$this->post('password/reset', 'Auth\ResetPasswordController@reset');

$this->middleware(['auth', 'apitoken'])->group(function() {
    $this->get('/', function() {
        return view('home', ['title' => 'Dashboard']);
    })->name('dash');
    $this->get('/home', function() {
        return view('home', ['title' => 'Dashboard']);
    })->name('home');
    $this->get('/manage/users', 'Management\UsersController@index')->name('management-users-index');
});
