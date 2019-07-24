<?php
Route::group(
    [
        'middleware' => ['auth:api', 'setlocale', 'bindings', 'sanitize'],
        'prefix' => 'api/1.0',
        'namespace' => 'ProcessMaker\Http\Controllers\Api',
        'as' => 'api.',
    ], function() {

    // Users
    Route::get('users', 'UserController@index')->name('users.index')->middleware('can:view-users');
    Route::get('users/{user}', 'UserController@show')->name('users.show'); //Permissions handled in the controller
    Route::post('users', 'UserController@store')->name('users.store')->middleware('can:create-users');
    Route::put('users/{user}', 'UserController@update')->name('users.update'); //Permissions handled in the controller
    Route::delete('users/{user}', 'UserController@destroy')->name('users.destroy')->middleware('can:delete-users');
    // User personal access tokens
    Route::get('users/{user}/tokens', 'UserTokenController@index')->name('users.tokens.index'); //Permissions handled in the controller
    Route::get('users/{user}/tokens/{tokenId}', 'UserTokenController@show')->name('users.tokens.show'); //Permissions handled in the controller
    Route::post('users/{user}/tokens', 'UserTokenController@store')->name('users.tokens.store'); // Permissions handled in the controller
    Route::delete('users/{user}/tokens/{tokenId}', 'UserTokenController@destroy')->name('users.tokens.destroy'); // Permissions handled in the controller


    // Groups
    Route::get('groups', 'GroupController@index')->name('groups.index')->middleware('can:view-groups');
    Route::get('groups/{group}', 'GroupController@show')->name('groups.show')->middleware('can:view-groups');
    Route::post('groups', 'GroupController@store')->name('groups.store')->middleware('can:create-groups');
    Route::put('groups/{group}', 'GroupController@update')->name('groups.update')->middleware('can:edit-groups');
    Route::delete('groups/{group}', 'GroupController@destroy')->name('groups.destroy')->middleware('can:delete-groups');
    Route::get('group_users/{group}', 'GroupController@members')->name('groups.members')->middleware('can:view-groups');

    // Group Members
    Route::get('group_members', 'GroupMemberController@index')->name('group_members.index'); //Already filtered in controller
    Route::get('group_members/{group_member}', 'GroupMemberController@show')->name('group_members.show')->middleware('can:view-groups');
    Route::get('group_members_available', 'GroupMemberController@groupsAvailable')->name('group_members_available.show')->middleware('can:view-groups');
    Route::get('user_members_available', 'GroupMemberController@usersAvailable')->name('user_members_available.show')->middleware('can:view-groups');
    Route::post('group_members', 'GroupMemberController@store')->name('group_members.store')->middleware('can:edit-groups');
    Route::delete('group_members/{group_member}', 'GroupMemberController@destroy')->name('group_members.destroy')->middleware('can:edit-groups');

    // Environment Variables
    Route::get('environment_variables', 'EnvironmentVariablesController@index')->name('environment_variables.index')->middleware('can:view-environment_variables');
    Route::get('environment_variables/{environment_variable}', 'EnvironmentVariablesController@show')->name('environment_variables.show')->middleware('can:view-environment_variables');
    Route::post('environment_variables', 'EnvironmentVariablesController@store')->name('environment_variables.store')->middleware('can:create-environment_variables');
    Route::put('environment_variables/{environment_variable}', 'EnvironmentVariablesController@update')->name('environment_variables.update')->middleware('can:edit-environment_variables');
    Route::delete('environment_variables/{environment_variable}', 'EnvironmentVariablesController@destroy')->name('environment_variables.destroy')->middleware('can:delete-environment_variables');

    // Screens
    Route::get('screens', 'ScreenController@index')->name('screens.index')->middleware('can:view-screens');
    Route::get('screens/{screen}', 'ScreenController@show')->name('screens.show')->middleware('can:view-screens');
    Route::post('screens', 'ScreenController@store')->name('screens.store')->middleware('can:create-screens');
    Route::put('screens/{screen}', 'ScreenController@update')->name('screens.update')->middleware('can:edit-screens');
    Route::put('screens/{screen}/duplicate', 'ScreenController@duplicate')->name('screens.duplicate')->middleware('can:create-screens');
    Route::delete('screens/{screen}', 'ScreenController@destroy')->name('screens.destroy')->middleware('can:delete-screens');
    Route::post('screens/{screen}/export', 'ScreenController@export')->name('screens.export')->middleware('can:export-screens');
    Route::post('screens/import', 'ScreenController@import')->name('screens.import')->middleware('can:import-screens');

    // Screen Categories
    Route::get('screen_categories', 'ScreenCategoryController@index')->name('screen_categories.index')->middleware('can:view-screens');
    Route::get('screen_categories/{screen_category}', 'ScreenCategoryController@show')->name('screen_categories.show')->middleware('can:view-screens');
    Route::post('screen_categories', 'ScreenCategoryController@store')->name('screen_categories.store')->middleware('can:edit-screens');
    Route::put('screen_categories/{screen_category}', 'ScreenCategoryController@update')->name('screen_categories.update')->middleware('can:edit-screens');
    Route::delete('screen_categories/{screen_category}', 'ScreenCategoryController@destroy')->name('screen_categories.destroy')->middleware('can:edit-screens');

    // Scripts
    Route::get('scripts', 'ScriptController@index')->name('scripts.index')->middleware('can:view-scripts');
    Route::get('scripts/{script}', 'ScriptController@show')->name('scripts.show')->middleware('can:view-scripts');
    Route::post('scripts', 'ScriptController@store')->name('scripts.store')->middleware('can:create-scripts');
    Route::put('scripts/{script}', 'ScriptController@update')->name('scripts.update')->middleware('can:edit-scripts');
    Route::put('scripts/{script}/duplicate', 'ScriptController@duplicate')->name('scripts.duplicate')->middleware('can:create-scripts');
    Route::delete('scripts/{script}', 'ScriptController@destroy')->name('scripts.destroy')->middleware('can:delete-scripts');
    Route::post('scripts/{script}/preview', 'ScriptController@preview')->name('script.preview')->middleware('can:view-scripts');

    // Processes
    Route::get('processes', 'ProcessController@index')->name('processes.index')->middleware('can:view-processes');
    Route::get('processes/{process}', 'ProcessController@show')->name('processes.show')->middleware('can:view-processes');
    Route::post('processes/{process}/export', 'ProcessController@export')->name('processes.export')->middleware('can:export-processes');
    Route::post('processes/import', 'ProcessController@import')->name('processes.import')->middleware('can:import-processes');
    Route::post('processes/{process}/import/assignments', 'ProcessController@importAssignments')->name('processes.import.assignments')->middleware('can:import-processes');
    Route::post('processes', 'ProcessController@store')->name('processes.store')->middleware('can:create-processes');
    Route::put('processes/{process}', 'ProcessController@update')->name('processes.update')->middleware('can:edit-processes');
    Route::delete('processes/{process}', 'ProcessController@destroy')->name('processes.destroy')->middleware('can:archive-processes');
    Route::put('processes/{processId}/restore', 'ProcessController@restore')->name('processes.restore')->middleware('can:archive-processes');
    Route::post('process_events/{process}', 'ProcessController@triggerStartEvent')->name('process_events.trigger')->middleware('can:start,process');

    // List of Processes that the user can start
    Route::get('start_processes', 'ProcessController@startProcesses')->name('processes.start'); //Filtered in controller

    // Process Categories
    Route::get('process_categories', 'ProcessCategoryController@index')->name('process_categories.index')->middleware('can:view-categories');
    Route::get('process_categories/{process_category}', 'ProcessCategoryController@show')->name('process_categories.show')->middleware('can:view-categories');
    Route::post('process_categories', 'ProcessCategoryController@store')->name('process_categories.store')->middleware('can:create-categories');
    Route::put('process_categories/{process_category}', 'ProcessCategoryController@update')->name('process_categories.update')->middleware('can:edit-categories');
    Route::delete('process_categories/{process_category}', 'ProcessCategoryController@destroy')->name('process_categories.destroy')->middleware('can:delete-categories');

    // Permissions
    Route::put('permissions', 'PermissionController@update')->name('permissions.update')->middleware('can:edit-users');

    // Tasks
    Route::get('tasks', 'TaskController@index')->name('tasks.index'); //Already filtered in controller
    Route::get('tasks/{task}', 'TaskController@show')->name('tasks.show')->middleware('can:view,task');
    Route::put('tasks/{task}', 'TaskController@update')->name('tasks.update')->middleware('can:update,task');

    // Requests
    Route::get('requests', 'ProcessRequestController@index')->name('requests.index'); //Already filtered in controller
    Route::get('requests/{request}', 'ProcessRequestController@show')->name('requests.show')->middleware('can:view,request');
    Route::put('requests/{request}', 'ProcessRequestController@update')->name('requests.update')->middleware('can:update,request');
    Route::delete('requests/{request}', 'ProcessRequestController@destroy')->name('requests.destroy')->middleware('can:destroy,request');
    Route::post('requests/{request}/events/{event}', 'ProcessRequestController@activateIntermediateEvent')->name('requests.update,request');

    // Request Files
    Route::get('requests/{request}/files', 'ProcessRequestFileController@index')->name('requests.files.index')->middleware('can:participate,request');
    Route::get('requests/{request}/files/{file}', 'ProcessRequestFileController@show')->name('requests.files.show')->middleware('can:participate,request');
    Route::post('requests/{request}/files', 'ProcessRequestFileController@store')->name('requests.files.store')->middleware('can:participate,request');
    Route::put('requests/{request}/files/{file}', 'ProcessRequestFileController@update')->name('requests.files.update')->middleware('can:participate,request');
    Route::delete('requests/{request}/files/{file}', 'ProcessRequestFileController@destroy')->name('requests.filesrequests.files.destroy')->middleware('can:participate,request');

    // Files
    Route::get('files', 'FileController@index')->name('files.index')->middleware('can:view-files');
    Route::get('files/{file}', 'FileController@show')->name('files.show')->middleware('can:view-files');
    Route::get('files/{file}/contents', 'FileController@download')->name('files.download')->middleware('can:view-files');
    Route::post('files', 'FileController@store')->name('files.store')->middleware('can:create-files');
    Route::put('files/{file}', 'FileController@update')->name('files.update')->middleware('can:edit-files');
    Route::delete('files/{file}', 'FileController@destroy')->name('files.destroy')->middleware('can:delete-files');

    // Notifications
    Route::get('notifications', 'NotificationController@index')->name('notifications.index');  //Already filtered in controller
    Route::get('notifications/{notification}', 'NotificationController@show')->name('notifications.show')->middleware('can:view,notification');
    Route::post('notifications', 'NotificationController@store')->name('notifications.store')->middleware('can:create,ProcessMaker\Models\Notification');
    Route::put('notifications/{notification}', 'NotificationController@update')->name('notifications.update')->middleware('can:edit,notification');
    Route::delete('notifications/{notification}', 'NotificationController@destroy')->name('notifications.destroy')->middleware('can:delete,notification');

    // Mark Notifications as Read & Unread
    Route::put('read_notifications', 'NotificationController@updateAsRead')->name('notifications.update_as_read'); //No permissions necessary
    Route::put('unread_notifications', 'NotificationController@updateAsUnread')->name('notifications.update_as_unread'); //No permissions necessary
    Route::put('read_all_notifications', 'NotificationController@updateAsReadAll')->name('notifications.update_as_read'); //No permissions necessary

    // Task Assignments
    Route::get('task_assignments', 'TaskAssignmentController@index')->name('task_assignments.index')->middleware('can:view-task_assignments');
    Route::post('task_assignments', 'TaskAssignmentController@store')->name('task_assignments.store')->middleware('can:create-task_assignments');
    Route::put('task_assignments/{task_assignment}', 'TaskAssignmentController@update')->name('task_assignments.update')->middleware('can:edit-task_assignments');
    Route::delete('task_assignments/{task_assignment}', 'TaskAssignmentController@destroy')->name('task_assignments.destroy')->middleware('can:delete-task_assignments');

    // Comments
    Route::get('comments', 'CommentController@index')->name('comments.index')->middleware('can:view-comments');
    Route::get('comments/{comment}', 'CommentController@show')->name('comments.show')->middleware('can:view-comments');
    Route::post('comments', 'CommentController@store')->name('comments.store')->middleware('can:create-comments');
    Route::put('comments/{comment}', 'CommentController@update')->name('comments.update')->middleware('can:edit-comments');
    Route::delete('comments/{comment}', 'CommentController@destroy')->name('comments.destroy')->middleware('can:delete-comments');

    //Css override
    Route::post('css_settings', 'CssOverride@store')->name('css.store');//->middleware('can:create-css');
    Route::put('css_settings', 'CssOverride@update')->name('css.update');//->middleware('can:create-css');


    // debugging javascript errors
    Route::post('debug', 'DebugController@store')->name('debug.store')->middleware('throttle');

    // Returns a json error message instead of HTML
    Route::fallback(function(){
        return response()->json(['error' => 'Not Found'], 404);
    })->name('fallback');
});
