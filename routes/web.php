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

    $this->get('/json', function() {
        return array(
          "total" => 200,
          "per_page" => 15,
          "current_page" => 1,
          "last_page" => 14,
          "next_page_url" => "http:\/\/vuetable.ratiw.net\/api\/users?page=2","prev_page_url" => null,
          "from" => 1,
          "to" => 15,
          'data' => array(
          array(
          'firstName' => 'Alan',
          'age' => '40',
          'phone' => '804-123-1231'
        ),
          array(
          'firstName' => 'Mila',
          'age' => '40',
          'phone' => '804-123-1231'
        ),
          array(
          'firstName' => 'Mila',
          'age' => '40',
          'phone' => '804-123-1231'
        ),
          array(
          'firstName' => 'Mila',
          'age' => '40',
          'phone' => '804-123-1231'
        ),
          array(
          'firstName' => 'Mila',
          'age' => '40',
          'phone' => '804-123-1231'
        ),
          array(
          'firstName' => 'Taylor',
          'age' => '40',
          'phone' => '804-123-1231'
        ),
          array(
          'firstName' => 'Taylor',
          'age' => '40',
          'phone' => '804-123-1231'
        ),
          array(
          'firstName' => 'Taylor',
          'age' => '40',
          'phone' => '804-123-1231'
        ),
          array(
          'firstName' => 'Taylor',
          'age' => '40',
          'phone' => '804-123-1231'
        ),
          array(
          'firstName' => 'Taylor',
          'age' => '40',
          'phone' => '804-123-1231'
        ),
          array(
          'firstName' => 'Taylor',
          'age' => '40',
          'phone' => '804-123-1231'
        )
      )
      );
    })->name('home');

    $this->get('/manage/users', 'Management\UsersController@index')->name('management-users-index');
});
