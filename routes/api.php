<?php

use ProcessMaker\Http\Controllers\TestStatusController;

Route::group(
    [
        'middleware' => ['auth:api', 'setlocale', 'bindings', 'sanitize'],
        'prefix' => 'api/1.0',
        'namespace' => 'ProcessMaker\Http\Controllers\Api',
        'as' => 'api.',
    ], function () {

    // Users
        Route::get('users', 'UserController@index')->name('users.index'); //Permissions handled in the controller
        Route::get('users/{user}', 'UserController@show')->name('users.show'); //Permissions handled in the controller
        Route::get('deleted_users', 'UserController@deletedUsers')->name('users.deletedUsers')->middleware('can:view-users');
        Route::post('users', 'UserController@store')->name('users.store')->middleware('can:create-users');
        Route::put('users/restore', 'UserController@restore')->name('users.restore')->middleware('can:create-users');
        Route::put('users/{user}', 'UserController@update')->name('users.update'); //Permissions handled in the controller
        Route::delete('users/{user}', 'UserController@destroy')->name('users.destroy')->middleware('can:delete-users');
        Route::put('password/change', 'ChangePasswordController@update')->name('password.update');
        // User Groups
        Route::put('users/{user}/groups', 'UserController@updateGroups')->name('users.groups.update')->middleware('can:edit-users');
        // User personal access tokens
        Route::get('users/{user}/tokens', 'UserTokenController@index')->name('users.tokens.index'); //Permissions handled in the controller
        Route::get('users/{user}/tokens/{tokenId}', 'UserTokenController@show')->name('users.tokens.show'); //Permissions handled in the controller
        Route::post('users/{user}/tokens', 'UserTokenController@store')->name('users.tokens.store'); // Permissions handled in the controller
        Route::delete('users/{user}/tokens/{tokenId}', 'UserTokenController@destroy')->name('users.tokens.destroy'); // Permissions handled in the controller

    // Groups//Permissions policy
        Route::get('groups', 'GroupController@index')->name('groups.index'); //Permissions handled in the controller
        Route::get('groups/{group}', 'GroupController@show')->name('groups.show'); //Permissions handled in the controller
        Route::post('groups', 'GroupController@store')->name('groups.store')->middleware('can:create-groups');
        Route::put('groups/{group}', 'GroupController@update')->name('groups.update')->middleware('can:edit-groups');
        Route::delete('groups/{group}', 'GroupController@destroy')->name('groups.destroy')->middleware('can:delete-groups');
        Route::get('groups/{group}/users', 'GroupController@users')->name('groups.users')->middleware('can:view-groups');
        Route::get('groups/{group}/groups', 'GroupController@groups')->name('groups.groups')->middleware('can:view-groups');

        // Group Members
        Route::get('group_members', 'GroupMemberController@index')->name('group_members.index'); //Already filtered in controller
        Route::get('group_members/{group_member}', 'GroupMemberController@show')->name('group_members.show')->middleware('can:view-groups');
        Route::get('group_members_available', 'GroupMemberController@groupsAvailable')->name('group_members_available.show'); //Permissions handled in the controller
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
        Route::get('screens', 'ScreenController@index')->name('screens.index'); //Permissions handled in the controller
        Route::get('screens/{screen}', 'ScreenController@show')->name('screens.show'); //Permissions handled in the controller
        Route::post('screens', 'ScreenController@store')->name('screens.store')->middleware('can:create-screens');
        Route::put('screens/{screen}', 'ScreenController@update')->name('screens.update')->middleware('can:edit-screens');
        Route::put('screens/{screen}/duplicate', 'ScreenController@duplicate')->name('screens.duplicate')->middleware('can:create-screens');
        Route::delete('screens/{screen}', 'ScreenController@destroy')->name('screens.destroy')->middleware('can:delete-screens');
        Route::post('screens/{screen}/export', 'ScreenController@export')->name('screens.export')->middleware('can:export-screens');
        Route::post('screens/import', 'ScreenController@import')->name('screens.import')->middleware('can:import-screens');

        // Screen Categories
        Route::get('screen_categories', 'ScreenCategoryController@index')->name('screen_categories.index')->middleware('can:view-screen-categories');
        Route::get('screen_categories/{screen_category}', 'ScreenCategoryController@show')->name('screen_categories.show')->middleware('can:view-screen-categories');
        Route::post('screen_categories', 'ScreenCategoryController@store')->name('screen_categories.store')->middleware('can:create-screen-categories');
        Route::put('screen_categories/{screen_category}', 'ScreenCategoryController@update')->name('screen_categories.update')->middleware('can:edit-screen-categories');
        Route::delete('screen_categories/{screen_category}', 'ScreenCategoryController@destroy')->name('screen_categories.destroy')->middleware('can:delete-screen-categories');

        // Scripts
        Route::get('scripts', 'ScriptController@index')->name('scripts.index')->middleware('can:view-scripts');
        Route::get('scripts/{script}', 'ScriptController@show')->name('scripts.show')->middleware('can:view-scripts');
        Route::post('scripts', 'ScriptController@store')->name('scripts.store')->middleware('can:create-scripts');
        Route::put('scripts/{script}', 'ScriptController@update')->name('scripts.update')->middleware('can:edit-scripts');
        Route::put('scripts/{script}/duplicate', 'ScriptController@duplicate')->name('scripts.duplicate')->middleware('can:create-scripts');
        Route::delete('scripts/{script}', 'ScriptController@destroy')->name('scripts.destroy')->middleware('can:delete-scripts');
        Route::post('scripts/{script}/preview', 'ScriptController@preview')->name('scripts.preview')->middleware('can:view-scripts');
        Route::post('scripts/execute/{script_id}/{script_key?}', 'ScriptController@execute')->name('scripts.execute');
        Route::get('scripts/execution/{key}', 'ScriptController@execution')->name('scripts.execution');

        // Script Categories
        Route::get('script_categories', 'ScriptCategoryController@index')->name('script_categories.index')->middleware('can:view-script-categories');
        Route::get('script_categories/{script_category}', 'ScriptCategoryController@show')->name('script_categories.show')->middleware('can:view-script-categories');
        Route::post('script_categories', 'ScriptCategoryController@store')->name('script_categories.store')->middleware('can:create-script-categories');
        Route::put('script_categories/{script_category}', 'ScriptCategoryController@update')->name('script_categories.update')->middleware('can:edit-script-categories');
        Route::delete('script_categories/{script_category}', 'ScriptCategoryController@destroy')->name('script_categories.destroy')->middleware('can:delete-script-categories');

        // Processes
        Route::get('processes', 'ProcessController@index')->name('processes.index')->middleware('can:view-processes');
        Route::get('processes/{process}', 'ProcessController@show')->name('processes.show')->middleware('can:view-processes');
        Route::post('processes/{process}/export', 'ProcessController@export')->name('processes.export')->middleware('can:export-processes');
        Route::post('processes/import', 'ProcessController@import')->name('processes.import')->middleware('can:import-processes');
        Route::get('processes/import/{code}/is_ready', 'ProcessController@import_ready')->name('processes.import_is_ready')->middleware('can:import-processes');
        Route::post('processes/{process}/import/assignments', 'ProcessController@importAssignments')->name('processes.import.assignments')->middleware('can:import-processes');
        Route::post('processes', 'ProcessController@store')->name('processes.store')->middleware('can:create-processes');
        Route::put('processes/{process}', 'ProcessController@update')->name('processes.update')->middleware('can:edit-processes');
        Route::delete('processes/{process}', 'ProcessController@destroy')->name('processes.destroy')->middleware('can:archive-processes');
        Route::put('processes/{processId}/restore', 'ProcessController@restore')->name('processes.restore')->middleware('can:archive-processes');
        Route::post('process_events/{process}', 'ProcessController@triggerStartEvent')->name('process_events.trigger')->middleware('can:start,process');

        // List of Processes that the user can start
        Route::get('start_processes', 'ProcessController@startProcesses')->name('processes.start'); //Filtered in controller

        // Process Categories
        Route::get('process_categories', 'ProcessCategoryController@index')->name('process_categories.index')->middleware('can:view-process-categories');
        Route::get('process_categories/{process_category}', 'ProcessCategoryController@show')->name('process_categories.show')->middleware('can:view-process-categories');
        Route::post('process_categories', 'ProcessCategoryController@store')->name('process_categories.store')->middleware('can:create-process-categories');
        Route::put('process_categories/{process_category}', 'ProcessCategoryController@update')->name('process_categories.update')->middleware('can:edit-process-categories');
        Route::delete('process_categories/{process_category}', 'ProcessCategoryController@destroy')->name('process_categories.destroy')->middleware('can:delete-process-categories');

        // Permissions
        Route::get('permissions', 'PermissionController@index')->name('permissions.index');
        Route::put('permissions', 'PermissionController@update')->name('permissions.update')->middleware('can:edit-users');

        // Tasks
        Route::get('tasks', 'TaskController@index')->name('tasks.index'); //Already filtered in controller
        Route::get('tasks/{task}', 'TaskController@show')->name('tasks.show')->middleware('can:view,task');
        Route::put('tasks/{task}', 'TaskController@update')->name('tasks.update')->middleware('can:update,task');
        Route::get('tasks/{task}/screens/{screen}', 'TaskController@getScreen')->name('tasks.get_screen')->middleware('can:viewScreen,task,screen');

        // Requests
        Route::get('requests', 'ProcessRequestController@index')->name('requests.index'); //Already filtered in controller
        Route::get('requests/{request}', 'ProcessRequestController@show')->name('requests.show')->middleware('can:view,request');
        Route::put('requests/{request}', 'ProcessRequestController@update')->name('requests.update')->middleware('can:update,request');
        Route::delete('requests/{request}', 'ProcessRequestController@destroy')->name('requests.destroy')->middleware('can:destroy,request');
        Route::post('requests/{request}/events/{event}', 'ProcessRequestController@activateIntermediateEvent')->name('requests.update,request');

        // Request Files
        Route::get('requests/{request}/files', 'ProcessRequestFileController@index')->name('requests.files.index')->middleware('can:view,request');
        Route::get('requests/{request}/files/{file}', 'ProcessRequestFileController@show')->name('requests.files.show')->middleware('can:view,request');
        Route::post('requests/{request}/files', 'ProcessRequestFileController@store')->name('requests.files.store')->middleware('can:participate,request');
        Route::delete('requests/{request}/files/{file}', 'ProcessRequestFileController@destroy')->name('requests.filesrequests.files.destroy')->middleware('can:participate,request');

        // Files
        Route::get('files', 'FileController@index')->name('files.index')->middleware('can:view-files');
        Route::get('files/{file}', 'FileController@show')->name('files.show')->middleware('can:view,file');
        Route::get('files/{file}/contents', 'FileController@download')->name('files.download')->middleware('can:view,file');
        Route::post('files', 'FileController@store')->name('files.store')->middleware('can:create,ProcessMaker\Models\Media');
        Route::put('files/{file}', 'FileController@update')->name('files.update')->middleware('can:update,file');
        Route::delete('files/{file}', 'FileController@destroy')->name('files.destroy')->middleware('can:delete,file');

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

        // Global signals
        Route::get('signals', 'SignalController@index')->name('signals.index')->middleware('can:view-signals');
        Route::get('signals/{signalId}', 'SignalController@show')->name('signals.show')->middleware('can:view-signals');
        Route::post('signals', 'SignalController@store')->name('signals.store')->middleware('can:create-signals');
        Route::put('signals/{signalId}', 'SignalController@update')->name('signals.update')->middleware('can:edit-signals');
        Route::delete('signals/{signalId}', 'SignalController@destroy')->name('signals.destroy')->middleware('can:delete-signals');

        //UI customization
        Route::post('customize-ui', 'CssOverrideController@store')->name('customize-ui.store');

        // Rebuild Script Executors
        Route::get('script-executors', 'ScriptExecutorController@index')->name('script-executors.index');
        Route::get('script-executors/available-languages', 'ScriptExecutorController@availableLanguages')->name('script-executors.available-languages');
        Route::put('script-executors/{script_executor}', 'ScriptExecutorController@update')->name('script-executors.update');
        Route::post('script-executors', 'ScriptExecutorController@store')->name('script-executors.store');
        Route::post('script-executors/cancel', 'ScriptExecutorController@cancel')->name('script-executors.cancel');
        Route::delete('script-executors/{script_executor}', 'ScriptExecutorController@delete')->name('script-executors.delete');

        // Security logs
        Route::get('security-logs', 'SecurityLogController@index')->name('security-logs.index')->middleware('can:view-security-logs');
        Route::get('security-logs/{securityLog}', 'SecurityLogController@show')->name('security-logs.show')->middleware('can:view-security-logs');

        // Settings
        Route::get('settings', 'SettingController@index')->name('settings.index')->middleware('can:view-settings');
        Route::get('settings/groups', 'SettingController@groups')->name('settings.groups')->middleware('can:view-settings');
        Route::post('settings/import', 'SettingController@import')->name('settings.import')->middleware('can:update-settings');
        Route::put('settings/{setting}', 'SettingController@update')->name('settings.update')->middleware('can:update-settings');
        Route::get('settings/group/{group}/buttons', 'SettingController@buttons')->name('settings.buttons')->middleware('can:view-settings')->where('group', '[A-Za-z0-9 -_]+');
        Route::post('settings/upload-file', 'SettingController@upload')->name('settings.upload-file')->middleware('can:update-settings');

        // debugging javascript errors
        Route::post('debug', 'DebugController@store')->name('debug.store')->middleware('throttle');

        // Returns a json error message instead of HTML
        Route::fallback(function () {
            return response()->json(['error' => 'Not Found'], 404);
        })->name('fallback');

        Route::get('/test_acknowledgement', [TestStatusController::class, 'testAcknowledgement'])->name('test.acknowledgement');
    });
