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

$this->middleware(['auth'])->group(function() {
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
    $this->get('/tasks/{view}/{process}/{instance}/{token}', 'Request\TokenController@openTask');

  // All the routes in this group and below are for testing purposes only

    $this->get('/tasks', function(){
      return view('tasks',['title' => 'Dashboard']);
    })->name('tasks');

    $this->get('/requests', function(){
      return view('requests.index',['title' => __('In Progress'), 'status' => '2']);
    })->name('requests');
    
    $this->get('/requests/drafts', function(){
      return view('requests.index',['title' => __('Drafts'), 'status' => '1']);
    })->name('requests.drafts');
    
    $this->get('/requests/completed', function(){
      return view('requests.index',['title' => __('Completed'), 'status' => '3']);
    })->name('requests.completed');
    
    $this->get('/requests/paused', function(){
      return view('requests.index',['title' => __('Paused'), 'status' => '0']);
    })->name('requests.paused');

    // For fetching the status of an open request
    $this->get('/requests/{instance}/status', ['uses' => 'Request\StatusController@status'])->name('request-status');

    // To execute actions after a request/task has been processed
    $this->get('/requests/{instance}/submitted', 'Request\RequestsController@requestSubmitted');

    $this->get('/admin', function(){
      return view('admin',['title' => 'Dashboard']);
    })->name('admin');

    $this->get('/admin/profile', function(){
      return view('profile',['title' => 'Dashboard']);
    })->name('profile');

    $this->get('/admin/preferences', 'Management\PreferencesController@index')->name('preferences');

    $this->get('/admin/about', 'Management\aboutController@index')->name('about');


    $this->get('/', 'HomeController@index')->name('home');

    Route::group([
        'middleware' => ['permission:PM_USERS']
    ], function() {
      $this->get('/manage/environment-variables', 'Management\EnvironmentVariablesController@index')->name('management-environment-variables');
    });


    Route::group([
        'middleware' => ['permission:PM_USERS']
    ], function() {
      $this->get('/manage/users', 'Management\UsersController@index')->name('management-users-index');
      $this->get('/manage/groups', 'Management\GroupsController@index')->name('management-groups-index');
    });

    Route::group([
        'middleware' => ['permission:PM_CASES']
    ], function() {
        $this->get('/process/{process}/tasks', 'Designer\TaskController@index')->name('processes-task-index');
        $this->get('/processes', 'Designer\ProcessController@index')->name('processes');
        $this->get('/processes/categories', 'Designer\ProcessCategoryController@index')
             ->name('process-categories-index');
    });

    $this->get('/designer/{process?}', 'Designer\ProcessController@show')->name('designer-edit-process');

    $this->get('/designer/{process}/form/{form}', 'Designer\FormController@show')->name('designer-edit-form');
    $this->get('/processes/{process}/script/{script}', 'Designer\ScriptController@show')->name('designer-edit-script');

});
