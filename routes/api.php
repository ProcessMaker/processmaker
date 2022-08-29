<?php

use Illuminate\Support\Facades\Route;
use ProcessMaker\Http\Controllers\ProcessMaker;
use ProcessMaker\Http\Controllers\TestStatusController;

Route::middleware('auth:api', 'setlocale', 'bindings', 'sanitize')->prefix('api/1.0')->name('api.')->group(function () {

    // Users
    Route::get('users', [ProcessMaker\Http\Controllers\Api\UserController::class, 'index'])->name('users.index'); //Permissions handled in the controller
    Route::get('users/{user}', [ProcessMaker\Http\Controllers\Api\UserController::class, 'show'])->name('users.show'); //Permissions handled in the controller
    Route::get('deleted_users', [ProcessMaker\Http\Controllers\Api\UserController::class, 'deletedUsers'])->name('users.deletedUsers')->middleware('can:view-users');
    Route::post('users', [ProcessMaker\Http\Controllers\Api\UserController::class, 'store'])->name('users.store')->middleware('can:create-users');
    Route::put('users/restore', [ProcessMaker\Http\Controllers\Api\UserController::class, 'restore'])->name('users.restore')->middleware('can:create-users');
    Route::put('users/{user}', [ProcessMaker\Http\Controllers\Api\UserController::class, 'update'])->name('users.update'); //Permissions handled in the controller
    Route::delete('users/{user}', [ProcessMaker\Http\Controllers\Api\UserController::class, 'destroy'])->name('users.destroy')->middleware('can:delete-users');
    Route::put('password/change', [ProcessMaker\Http\Controllers\Api\ChangePasswordController::class, 'update'])->name('password.update');
    // User Groups
    Route::put('users/{user}/groups', [ProcessMaker\Http\Controllers\Api\UserController::class, 'updateGroups'])->name('users.groups.update')->middleware('can:edit-users');
    // User personal access tokens
    Route::get('users/{user}/tokens', [ProcessMaker\Http\Controllers\Api\UserTokenController::class, 'index'])->name('users.tokens.index'); //Permissions handled in the controller
    Route::get('users/{user}/tokens/{tokenId}', [ProcessMaker\Http\Controllers\Api\UserTokenController::class, 'show'])->name('users.tokens.show'); //Permissions handled in the controller
    Route::post('users/{user}/tokens', [ProcessMaker\Http\Controllers\Api\UserTokenController::class, 'store'])->name('users.tokens.store'); // Permissions handled in the controller
    Route::delete('users/{user}/tokens/{tokenId}', [ProcessMaker\Http\Controllers\Api\UserTokenController::class, 'destroy'])->name('users.tokens.destroy'); // Permissions handled in the controller

    // Groups//Permissions policy
    Route::get('groups', [ProcessMaker\Http\Controllers\Api\GroupController::class, 'index'])->name('groups.index'); //Permissions handled in the controller
    Route::get('groups/{group}', [ProcessMaker\Http\Controllers\Api\GroupController::class, 'show'])->name('groups.show'); //Permissions handled in the controller
    Route::post('groups', [ProcessMaker\Http\Controllers\Api\GroupController::class, 'store'])->name('groups.store')->middleware('can:create-groups');
    Route::put('groups/{group}', [ProcessMaker\Http\Controllers\Api\GroupController::class, 'update'])->name('groups.update')->middleware('can:edit-groups');
    Route::delete('groups/{group}', [ProcessMaker\Http\Controllers\Api\GroupController::class, 'destroy'])->name('groups.destroy')->middleware('can:delete-groups');
    Route::get('groups/{group}/users', [ProcessMaker\Http\Controllers\Api\GroupController::class, 'users'])->name('groups.users')->middleware('can:view-groups');
    Route::get('groups/{group}/groups', [ProcessMaker\Http\Controllers\Api\GroupController::class, 'groups'])->name('groups.groups')->middleware('can:view-groups');

    // Group Members
    Route::get('group_members', [ProcessMaker\Http\Controllers\Api\GroupMemberController::class, 'index'])->name('group_members.index'); //Already filtered in controller
    Route::get('group_members/{group_member}', [ProcessMaker\Http\Controllers\Api\GroupMemberController::class, 'show'])->name('group_members.show')->middleware('can:view-groups');
    Route::get('group_members_available', [ProcessMaker\Http\Controllers\Api\GroupMemberController::class, 'groupsAvailable'])->name('group_members_available.show'); //Permissions handled in the controller
    Route::get('user_members_available', [ProcessMaker\Http\Controllers\Api\GroupMemberController::class, 'usersAvailable'])->name('user_members_available.show')->middleware('can:view-groups');
    Route::post('group_members', [ProcessMaker\Http\Controllers\Api\GroupMemberController::class, 'store'])->name('group_members.store')->middleware('can:edit-groups');
    Route::delete('group_members/{group_member}', [ProcessMaker\Http\Controllers\Api\GroupMemberController::class, 'destroy'])->name('group_members.destroy')->middleware('can:edit-groups');

    // Environment Variables
    Route::get('environment_variables', [ProcessMaker\Http\Controllers\Api\EnvironmentVariablesController::class, 'index'])->name('environment_variables.index')->middleware('can:view-environment_variables');
    Route::get('environment_variables/{environment_variable}', [ProcessMaker\Http\Controllers\Api\EnvironmentVariablesController::class, 'show'])->name('environment_variables.show')->middleware('can:view-environment_variables');
    Route::post('environment_variables', [ProcessMaker\Http\Controllers\Api\EnvironmentVariablesController::class, 'store'])->name('environment_variables.store')->middleware('can:create-environment_variables');
    Route::put('environment_variables/{environment_variable}', [ProcessMaker\Http\Controllers\Api\EnvironmentVariablesController::class, 'update'])->name('environment_variables.update')->middleware('can:edit-environment_variables');
    Route::delete('environment_variables/{environment_variable}', [ProcessMaker\Http\Controllers\Api\EnvironmentVariablesController::class, 'destroy'])->name('environment_variables.destroy')->middleware('can:delete-environment_variables');

    // Screens
    Route::get('screens', [ProcessMaker\Http\Controllers\Api\ScreenController::class, 'index'])->name('screens.index'); //Permissions handled in the controller
    Route::get('screens/{screen}', [ProcessMaker\Http\Controllers\Api\ScreenController::class, 'show'])->name('screens.show'); //Permissions handled in the controller
    Route::post('screens', [ProcessMaker\Http\Controllers\Api\ScreenController::class, 'store'])->name('screens.store')->middleware('can:create-screens');
    Route::put('screens/{screen}', [ProcessMaker\Http\Controllers\Api\ScreenController::class, 'update'])->name('screens.update')->middleware('can:edit-screens');
    Route::put('screens/{screen}/duplicate', [ProcessMaker\Http\Controllers\Api\ScreenController::class, 'duplicate'])->name('screens.duplicate')->middleware('can:create-screens');
    Route::delete('screens/{screen}', [ProcessMaker\Http\Controllers\Api\ScreenController::class, 'destroy'])->name('screens.destroy')->middleware('can:delete-screens');
    Route::post('screens/{screen}/export', [ProcessMaker\Http\Controllers\Api\ScreenController::class, 'export'])->name('screens.export')->middleware('can:export-screens');
    Route::post('screens/import', [ProcessMaker\Http\Controllers\Api\ScreenController::class, 'import'])->name('screens.import')->middleware('can:import-screens');

    // Screen Categories
    Route::get('screen_categories', [ProcessMaker\Http\Controllers\Api\ScreenCategoryController::class, 'index'])->name('screen_categories.index')->middleware('can:view-screen-categories');
    Route::get('screen_categories/{screen_category}', [ProcessMaker\Http\Controllers\Api\ScreenCategoryController::class, 'show'])->name('screen_categories.show')->middleware('can:view-screen-categories');
    Route::post('screen_categories', [ProcessMaker\Http\Controllers\Api\ScreenCategoryController::class, 'store'])->name('screen_categories.store')->middleware('can:create-screen-categories');
    Route::put('screen_categories/{screen_category}', [ProcessMaker\Http\Controllers\Api\ScreenCategoryController::class, 'update'])->name('screen_categories.update')->middleware('can:edit-screen-categories');
    Route::delete('screen_categories/{screen_category}', [ProcessMaker\Http\Controllers\Api\ScreenCategoryController::class, 'destroy'])->name('screen_categories.destroy')->middleware('can:delete-screen-categories');

    // Scripts
    Route::get('scripts', [ProcessMaker\Http\Controllers\Api\ScriptController::class, 'index'])->name('scripts.index')->middleware('can:view-scripts');
    Route::get('scripts/{script}', [ProcessMaker\Http\Controllers\Api\ScriptController::class, 'show'])->name('scripts.show')->middleware('can:view-scripts');
    Route::post('scripts', [ProcessMaker\Http\Controllers\Api\ScriptController::class, 'store'])->name('scripts.store')->middleware('can:create-scripts');
    Route::put('scripts/{script}', [ProcessMaker\Http\Controllers\Api\ScriptController::class, 'update'])->name('scripts.update')->middleware('can:edit-scripts');
    Route::put('scripts/{script}/duplicate', [ProcessMaker\Http\Controllers\Api\ScriptController::class, 'duplicate'])->name('scripts.duplicate')->middleware('can:create-scripts');
    Route::delete('scripts/{script}', [ProcessMaker\Http\Controllers\Api\ScriptController::class, 'destroy'])->name('scripts.destroy')->middleware('can:delete-scripts');
    Route::post('scripts/{script}/preview', [ProcessMaker\Http\Controllers\Api\ScriptController::class, 'preview'])->name('scripts.preview')->middleware('can:view-scripts');
    Route::post('scripts/execute/{script_id}/{script_key?}', [ProcessMaker\Http\Controllers\Api\ScriptController::class, 'execute'])->name('scripts.execute');
    Route::get('scripts/execution/{key}', [ProcessMaker\Http\Controllers\Api\ScriptController::class, 'execution'])->name('scripts.execution');

    // Script Categories
    Route::get('script_categories', [ProcessMaker\Http\Controllers\Api\ScriptCategoryController::class, 'index'])->name('script_categories.index')->middleware('can:view-script-categories');
    Route::get('script_categories/{script_category}', [ProcessMaker\Http\Controllers\Api\ScriptCategoryController::class, 'show'])->name('script_categories.show')->middleware('can:view-script-categories');
    Route::post('script_categories', [ProcessMaker\Http\Controllers\Api\ScriptCategoryController::class, 'store'])->name('script_categories.store')->middleware('can:create-script-categories');
    Route::put('script_categories/{script_category}', [ProcessMaker\Http\Controllers\Api\ScriptCategoryController::class, 'update'])->name('script_categories.update')->middleware('can:edit-script-categories');
    Route::delete('script_categories/{script_category}', [ProcessMaker\Http\Controllers\Api\ScriptCategoryController::class, 'destroy'])->name('script_categories.destroy')->middleware('can:delete-script-categories');

    // Processes
    Route::get('processes', [ProcessMaker\Http\Controllers\Api\ProcessController::class, 'index'])->name('processes.index')->middleware('can:view-processes');
    Route::get('processes/{process}', [ProcessMaker\Http\Controllers\Api\ProcessController::class, 'show'])->name('processes.show')->middleware('can:view-processes');
    Route::post('processes/{process}/export', [ProcessMaker\Http\Controllers\Api\ProcessController::class, 'export'])->name('processes.export')->middleware('can:export-processes');
    Route::post('processes/import', [ProcessMaker\Http\Controllers\Api\ProcessController::class, 'import'])->name('processes.import')->middleware('can:import-processes');
    Route::get('processes/import/{code}/is_ready', [ProcessMaker\Http\Controllers\Api\ProcessController::class, 'import_ready'])->name('processes.import_is_ready')->middleware('can:import-processes');
    Route::post('processes/{process}/import/assignments', [ProcessMaker\Http\Controllers\Api\ProcessController::class, 'importAssignments'])->name('processes.import.assignments')->middleware('can:import-processes');
    Route::post('processes', [ProcessMaker\Http\Controllers\Api\ProcessController::class, 'store'])->name('processes.store')->middleware('can:create-processes');
    Route::put('processes/{process}', [ProcessMaker\Http\Controllers\Api\ProcessController::class, 'update'])->name('processes.update')->middleware('can:edit-processes');
    Route::delete('processes/{process}', [ProcessMaker\Http\Controllers\Api\ProcessController::class, 'destroy'])->name('processes.destroy')->middleware('can:archive-processes');
    Route::put('processes/{processId}/restore', [ProcessMaker\Http\Controllers\Api\ProcessController::class, 'restore'])->name('processes.restore')->middleware('can:archive-processes');
    Route::post('process_events/{process}', [ProcessMaker\Http\Controllers\Api\ProcessController::class, 'triggerStartEvent'])->name('process_events.trigger')->middleware('can:start,process');

    // List of Processes that the user can start
    Route::get('start_processes', [ProcessMaker\Http\Controllers\Api\ProcessController::class, 'startProcesses'])->name('processes.start'); //Filtered in controller

    // Process Categories
    Route::get('process_categories', [ProcessMaker\Http\Controllers\Api\ProcessCategoryController::class, 'index'])->name('process_categories.index')->middleware('can:view-process-categories');
    Route::get('process_categories/{process_category}', [ProcessMaker\Http\Controllers\Api\ProcessCategoryController::class, 'show'])->name('process_categories.show')->middleware('can:view-process-categories');
    Route::post('process_categories', [ProcessMaker\Http\Controllers\Api\ProcessCategoryController::class, 'store'])->name('process_categories.store')->middleware('can:create-process-categories');
    Route::put('process_categories/{process_category}', [ProcessMaker\Http\Controllers\Api\ProcessCategoryController::class, 'update'])->name('process_categories.update')->middleware('can:edit-process-categories');
    Route::delete('process_categories/{process_category}', [ProcessMaker\Http\Controllers\Api\ProcessCategoryController::class, 'destroy'])->name('process_categories.destroy')->middleware('can:delete-process-categories');

    // Permissions
    Route::get('permissions', [ProcessMaker\Http\Controllers\Api\PermissionController::class, 'index'])->name('permissions.index');
    Route::put('permissions', [ProcessMaker\Http\Controllers\Api\PermissionController::class, 'update'])->name('permissions.update')->middleware('can:edit-users');

    // Tasks
    Route::get('tasks', [ProcessMaker\Http\Controllers\Api\TaskController::class, 'index'])->name('tasks.index'); //Already filtered in controller
    Route::get('tasks/{task}', [ProcessMaker\Http\Controllers\Api\TaskController::class, 'show'])->name('tasks.show')->middleware('can:view,task');
    Route::put('tasks/{task}', [ProcessMaker\Http\Controllers\Api\TaskController::class, 'update'])->name('tasks.update')->middleware('can:update,task');
    Route::get('tasks/{task}/screens/{screen}', [ProcessMaker\Http\Controllers\Api\TaskController::class, 'getScreen'])->name('tasks.get_screen')->middleware('can:viewScreen,task,screen');

    // Requests
    Route::get('requests', [ProcessMaker\Http\Controllers\Api\ProcessRequestController::class, 'index'])->name('requests.index'); //Already filtered in controller
    Route::get('requests/{request}', [ProcessMaker\Http\Controllers\Api\ProcessRequestController::class, 'show'])->name('requests.show')->middleware('can:view,request');
    Route::put('requests/{request}', [ProcessMaker\Http\Controllers\Api\ProcessRequestController::class, 'update'])->name('requests.update')->middleware('can:update,request');
    Route::delete('requests/{request}', [ProcessMaker\Http\Controllers\Api\ProcessRequestController::class, 'destroy'])->name('requests.destroy')->middleware('can:destroy,request');
    Route::post('requests/{request}/events/{event}', [ProcessMaker\Http\Controllers\Api\ProcessRequestController::class, 'activateIntermediateEvent'])->name('requests.update,request');

    // Request Files
    Route::get('requests/{request}/files', [ProcessMaker\Http\Controllers\Api\ProcessRequestFileController::class, 'index'])->name('requests.files.index')->middleware('can:view,request');
    Route::get('requests/{request}/files/{file}', [ProcessMaker\Http\Controllers\Api\ProcessRequestFileController::class, 'show'])->name('requests.files.show')->middleware('can:view,request');
    Route::post('requests/{request}/files', [ProcessMaker\Http\Controllers\Api\ProcessRequestFileController::class, 'store'])->name('requests.files.store')->middleware('can:participate,request');
    Route::delete('requests/{request}/files/{file}', [ProcessMaker\Http\Controllers\Api\ProcessRequestFileController::class, 'destroy'])->name('requests.filesrequests.files.destroy')->middleware('can:participate,request');

    // Files
    Route::get('files', [ProcessMaker\Http\Controllers\Api\FileController::class, 'index'])->name('files.index')->middleware('can:view-files');
    Route::get('files/{file}', [ProcessMaker\Http\Controllers\Api\FileController::class, 'show'])->name('files.show')->middleware('can:view,file');
    Route::get('files/{file}/contents', [ProcessMaker\Http\Controllers\Api\FileController::class, 'download'])->name('files.download')->middleware('can:view,file');
    Route::post('files', [ProcessMaker\Http\Controllers\Api\FileController::class, 'store'])->name('files.store')->middleware('can:create,ProcessMaker\Models\Media');
    Route::put('files/{file}', [ProcessMaker\Http\Controllers\Api\FileController::class, 'update'])->name('files.update')->middleware('can:update,file');
    Route::delete('files/{file}', [ProcessMaker\Http\Controllers\Api\FileController::class, 'destroy'])->name('files.destroy')->middleware('can:delete,file');

    // Notifications
    Route::get('notifications', [ProcessMaker\Http\Controllers\Api\NotificationController::class, 'index'])->name('notifications.index');  //Already filtered in controller
    Route::get('notifications/{notification}', [ProcessMaker\Http\Controllers\Api\NotificationController::class, 'show'])->name('notifications.show')->middleware('can:view,notification');
    Route::post('notifications', [ProcessMaker\Http\Controllers\Api\NotificationController::class, 'store'])->name('notifications.store')->middleware('can:create,ProcessMaker\Models\Notification');
    Route::put('notifications/{notification}', [ProcessMaker\Http\Controllers\Api\NotificationController::class, 'update'])->name('notifications.update')->middleware('can:edit,notification');
    Route::delete('notifications/{notification}', [ProcessMaker\Http\Controllers\Api\NotificationController::class, 'destroy'])->name('notifications.destroy')->middleware('can:delete,notification');

    // Mark Notifications as Read & Unread
    Route::put('read_notifications', [ProcessMaker\Http\Controllers\Api\NotificationController::class, 'updateAsRead'])->name('notifications.update_as_read'); //No permissions necessary
    Route::put('unread_notifications', [ProcessMaker\Http\Controllers\Api\NotificationController::class, 'updateAsUnread'])->name('notifications.update_as_unread'); //No permissions necessary
    Route::put('read_all_notifications', [ProcessMaker\Http\Controllers\Api\NotificationController::class, 'updateAsReadAll'])->name('notifications.update_as_read'); //No permissions necessary

    // Task Assignments
    Route::get('task_assignments', [ProcessMaker\Http\Controllers\Api\TaskAssignmentController::class, 'index'])->name('task_assignments.index')->middleware('can:view-task_assignments');
    Route::post('task_assignments', [ProcessMaker\Http\Controllers\Api\TaskAssignmentController::class, 'store'])->name('task_assignments.store')->middleware('can:create-task_assignments');
    Route::put('task_assignments/{task_assignment}', [ProcessMaker\Http\Controllers\Api\TaskAssignmentController::class, 'update'])->name('task_assignments.update')->middleware('can:edit-task_assignments');
    Route::delete('task_assignments/{task_assignment}', [ProcessMaker\Http\Controllers\Api\TaskAssignmentController::class, 'destroy'])->name('task_assignments.destroy')->middleware('can:delete-task_assignments');

    // Comments
    Route::get('comments', [ProcessMaker\Http\Controllers\Api\CommentController::class, 'index'])->name('comments.index')->middleware('can:view-comments');
    Route::get('comments/{comment}', [ProcessMaker\Http\Controllers\Api\CommentController::class, 'show'])->name('comments.show')->middleware('can:view-comments');
    Route::post('comments', [ProcessMaker\Http\Controllers\Api\CommentController::class, 'store'])->name('comments.store')->middleware('can:create-comments');
    Route::put('comments/{comment}', [ProcessMaker\Http\Controllers\Api\CommentController::class, 'update'])->name('comments.update')->middleware('can:edit-comments');
    Route::delete('comments/{comment}', [ProcessMaker\Http\Controllers\Api\CommentController::class, 'destroy'])->name('comments.destroy')->middleware('can:delete-comments');

    // Global signals
    Route::get('signals', [ProcessMaker\Http\Controllers\Api\SignalController::class, 'index'])->name('signals.index')->middleware('can:view-signals');
    Route::get('signals/{signalId}', [ProcessMaker\Http\Controllers\Api\SignalController::class, 'show'])->name('signals.show')->middleware('can:view-signals');
    Route::post('signals', [ProcessMaker\Http\Controllers\Api\SignalController::class, 'store'])->name('signals.store')->middleware('can:create-signals');
    Route::put('signals/{signalId}', [ProcessMaker\Http\Controllers\Api\SignalController::class, 'update'])->name('signals.update')->middleware('can:edit-signals');
    Route::delete('signals/{signalId}', [ProcessMaker\Http\Controllers\Api\SignalController::class, 'destroy'])->name('signals.destroy')->middleware('can:delete-signals');

    //UI customization
    Route::post('customize-ui', [ProcessMaker\Http\Controllers\Api\CssOverrideController::class, 'store'])->name('customize-ui.store');

    // Rebuild Script Executors
    Route::get('script-executors', [ProcessMaker\Http\Controllers\Api\ScriptExecutorController::class, 'index'])->name('script-executors.index');
    Route::get('script-executors/available-languages', [ProcessMaker\Http\Controllers\Api\ScriptExecutorController::class, 'availableLanguages'])->name('script-executors.available-languages');
    Route::put('script-executors/{script_executor}', [ProcessMaker\Http\Controllers\Api\ScriptExecutorController::class, 'update'])->name('script-executors.update');
    Route::post('script-executors', [ProcessMaker\Http\Controllers\Api\ScriptExecutorController::class, 'store'])->name('script-executors.store');
    Route::post('script-executors/cancel', [ProcessMaker\Http\Controllers\Api\ScriptExecutorController::class, 'cancel'])->name('script-executors.cancel');
    Route::delete('script-executors/{script_executor}', [ProcessMaker\Http\Controllers\Api\ScriptExecutorController::class, 'delete'])->name('script-executors.delete');

    // Security logs
    Route::get('security-logs', [ProcessMaker\Http\Controllers\Api\SecurityLogController::class, 'index'])->name('security-logs.index')->middleware('can:view-security-logs');
    Route::get('security-logs/{securityLog}', [ProcessMaker\Http\Controllers\Api\SecurityLogController::class, 'show'])->name('security-logs.show')->middleware('can:view-security-logs');

    // Settings
    Route::get('settings', [ProcessMaker\Http\Controllers\Api\SettingController::class, 'index'])->name('settings.index')->middleware('can:view-settings');
    Route::get('settings/groups', [ProcessMaker\Http\Controllers\Api\SettingController::class, 'groups'])->name('settings.groups')->middleware('can:view-settings');
    Route::post('settings/import', [ProcessMaker\Http\Controllers\Api\SettingController::class, 'import'])->name('settings.import')->middleware('can:update-settings');
    Route::put('settings/{setting}', [ProcessMaker\Http\Controllers\Api\SettingController::class, 'update'])->name('settings.update')->middleware('can:update-settings');
    Route::get('settings/group/{group}/buttons', [ProcessMaker\Http\Controllers\Api\SettingController::class, 'buttons'])->name('settings.buttons')->middleware('can:view-settings')->where('group', '[A-Za-z0-9 -_]+');
    Route::post('settings/upload-file', [ProcessMaker\Http\Controllers\Api\SettingController::class, 'upload'])->name('settings.upload-file')->middleware('can:update-settings');

    // debugging javascript errors
    Route::post('debug', [ProcessMaker\Http\Controllers\Api\DebugController::class, 'store'])->name('debug.store')->middleware('throttle');

    // Returns a json error message instead of HTML
    Route::fallback(function () {
        return response()->json(['error' => 'Not Found'], 404);
    })->name('fallback');

    Route::get('/test_acknowledgement', [TestStatusController::class, 'testAcknowledgement'])->name('test.acknowledgement');
});
