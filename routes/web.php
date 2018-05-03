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

  // All the routes in this group and below are for testing purposes only

    $this->get('/build', function(){
      return view('build',['title' => 'Dashboard']);
    })->name('build');

    $this->get('/manage', function(){
      return view('manage',['title' => 'Dashboard']);
    })->name('manage');

    $this->get('/', function() {
        return view('home', ['title' => 'Dashboard']);
    })->name('dash');

    $this->get('/home', function() {
        return view('home', ['title' => 'Dashboard']);
    })->name('home');
    
    $this->get('/userprofile', function() {
        return view('userprofile', ['title' => 'Dashboard']);
    })->name('userprofile');

    $this->get('/manage/users', 'Management\UsersController@index')->name('management-users-index');
});


$this->get('/designer', function() {
    return view('designer', ['title' => 'Designer']);
})->name('designer');
