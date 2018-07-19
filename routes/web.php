<?php

// Routes related to Authentication (password reset, etc)
Auth::routes();

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
$this->get('password/success', function(){
  return view('auth.passwords.success',['title' => __('Password Reset')]);
})->name('password-success');

// Password Reset Routes...
// $this->get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
// $this->post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.email');
// $this->get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');
// $this->post('password/reset', 'Auth\ResetPasswordController@reset');

// $this->get('password/reset/{token?}', 'Auth\PasswordController@showResetForm');
// $this->post('password/email', 'Auth\PasswordController@sendResetLinkEmail');
// $this->post('password/reset', 'Auth\PasswordController@reset');

$this->middleware(['auth', 'apitoken'])->group(function() {
    // Test Process Routes for Nayra
    $this->get('/requests/{process}/new', function(ProcessMaker\Model\Process $process) {
        //Find the process
        $processes = $process->getDefinitions()->getElementsByTagName('process');
        if ($processes->item(0)) {
            $processDefinition = $processes->item(0)->getBpmnElementInstance();
            $processId = $processDefinition->getId();
            return view('nayra.process', compact('process', 'processId'));
        }
    });
    $this->get('/nayra/request/{process}/{event}', function(ProcessMaker\Model\Process $process, $event) {
        return view('nayra.start', compact('process', 'event'));
    });
    $this->get('/nayra/{view}/{process}/{instance}/{token}', 'Request\TokenController@openTask');

  // All the routes in this group and below are for testing purposes only

    $this->get('/task', function(){
      return view('task',['title' => 'Dashboard']);
    })->name('task'); $this->get('/process', function(){ return view('process',['title' => 'Dashboard']);
    })->name('process');

    $this->get('/request', function(){
      return view('request.index',['title' => __('New Request')]);
    })->name('request');

    // For fetching the status of an open case/request
    $this->get('/request/{instance}/status', ['uses' => 'Request\StatusController@status'])->name('request-status');

    $this->get('/admin', function(){
      return view('admin',['title' => 'Dashboard']);
    })->name('admin');

    $this->get('/admin/profile', function(){
      return view('profile',['title' => 'Dashboard']);
    })->name('profile');

    $this->get('/', function() {
        return view('home', ['title' => 'Dashboard']);
    })->name('dash');

    $this->get('/home', function() {
        return view('home', ['title' => 'Dashboard']);
    })->name('home');

    Route::group([
        'middleware' => ['permission:PM_USERS']
    ], function() {
      $this->get('/manage/users', 'Management\UsersController@index')->name('management-users-index');
      $this->get('/manage/roles', 'Management\RolesController@index')->name('management-roles-index');
      $this->get('/manage/groups', 'Management\GroupsController@index')->name('management-groups-index');
    });

    Route::group([
        'middleware' => ['permission:PM_CASES']
    ], function() {
        $this->get('/process/{process}/tasks', 'Designer\TaskController@index')->name('processes-task-index');
        $this->get('/processes', 'Designer\ProcessController@index')->name('process');
    });

    $this->get('/designer', function() {
        return view('designer.designer', ['title' => 'Designer']);
    })->name('designer');
});
