<?php

use Illuminate\Support\Facades\Route;
use ProcessMaker\Http\Controllers\Api\ChangePasswordController;
use ProcessMaker\Http\Controllers\Api\CommentController;
use ProcessMaker\Http\Controllers\Api\CssOverrideController;
use ProcessMaker\Http\Controllers\Api\DebugController;
use ProcessMaker\Http\Controllers\Api\EnvironmentVariablesController;
use ProcessMaker\Http\Controllers\Api\ExportController;
use ProcessMaker\Http\Controllers\Api\FileController;
use ProcessMaker\Http\Controllers\Api\GroupController;
use ProcessMaker\Http\Controllers\Api\GroupMemberController;
use ProcessMaker\Http\Controllers\Api\ImportController;
use ProcessMaker\Http\Controllers\Api\NotificationController;
use ProcessMaker\Http\Controllers\Api\OpenAIController;
use ProcessMaker\Http\Controllers\Api\PermissionController;
use ProcessMaker\Http\Controllers\Api\ProcessCategoryController;
use ProcessMaker\Http\Controllers\Api\ProcessController;
use ProcessMaker\Http\Controllers\Api\ProcessRequestController;
use ProcessMaker\Http\Controllers\Api\ProcessRequestFileController;
use ProcessMaker\Http\Controllers\Api\ScreenCategoryController;
use ProcessMaker\Http\Controllers\Api\ScreenController;
use ProcessMaker\Http\Controllers\Api\ScriptCategoryController;
use ProcessMaker\Http\Controllers\Api\ScriptController;
use ProcessMaker\Http\Controllers\Api\ScriptExecutorController;
use ProcessMaker\Http\Controllers\Api\SecurityLogController;
use ProcessMaker\Http\Controllers\Api\SettingController;
use ProcessMaker\Http\Controllers\Api\SignalController;
use ProcessMaker\Http\Controllers\Api\TaskAssignmentController;
use ProcessMaker\Http\Controllers\Api\TaskController;
use ProcessMaker\Http\Controllers\Api\TemplateController;
use ProcessMaker\Http\Controllers\Api\UserController;
use ProcessMaker\Http\Controllers\Api\UserTokenController;
use ProcessMaker\Http\Controllers\Process\ModelerController;
use ProcessMaker\Http\Controllers\TestStatusController;

Route::middleware('auth:api', 'setlocale', 'bindings', 'sanitize')->prefix('api/1.0')->name('api.')->group(function () {
    // Users
    Route::get('users', [UserController::class, 'index'])->name('users.index'); // Permissions handled in the controller
    Route::get('users/{user}', [UserController::class, 'show'])->name('users.show'); // Permissions handled in the controller
    Route::get('deleted_users', [UserController::class, 'deletedUsers'])->name('users.deletedUsers')->middleware('can:view-users');
    Route::post('users', [UserController::class, 'store'])->name('users.store')->middleware('can:create-users');
    Route::put('users/restore', [UserController::class, 'restore'])->name('users.restore')->middleware('can:create-users');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update'); // Permissions handled in the controller
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy')->middleware('can:delete-users');
    Route::put('password/change', [ChangePasswordController::class, 'update'])->name('password.update');
    // User Groups
    Route::put('users/{user}/groups', [UserController::class, 'updateGroups'])->name('users.groups.update')->middleware('can:edit-users');
    // User personal access tokens
    Route::get('users/{user}/tokens', [UserTokenController::class, 'index'])->name('users.tokens.index'); // Permissions handled in the controller
    Route::get('users/{user}/tokens/{tokenId}', [UserTokenController::class, 'show'])->name('users.tokens.show'); // Permissions handled in the controller
    Route::post('users/{user}/tokens', [UserTokenController::class, 'store'])->name('users.tokens.store'); // Permissions handled in the controller
    Route::delete('users/{user}/tokens/{tokenId}', [UserTokenController::class, 'destroy'])->name('users.tokens.destroy'); // Permissions handled in the controller

    // Groups//Permissions policy
    Route::get('groups', [GroupController::class, 'index'])->name('groups.index'); // Permissions handled in the controller
    Route::get('groups/{group}', [GroupController::class, 'show'])->name('groups.show'); // Permissions handled in the controller
    Route::post('groups', [GroupController::class, 'store'])->name('groups.store')->middleware('can:create-groups');
    Route::put('groups/{group}', [GroupController::class, 'update'])->name('groups.update')->middleware('can:edit-groups');
    Route::delete('groups/{group}', [GroupController::class, 'destroy'])->name('groups.destroy')->middleware('can:delete-groups');
    Route::get('groups/{group}/users', [GroupController::class, 'users'])->name('groups.users')->middleware('can:view-groups');
    Route::get('groups/{group}/groups', [GroupController::class, 'groups'])->name('groups.groups')->middleware('can:view-groups');

    // Group Members
    Route::get('group_members', [GroupMemberController::class, 'index'])->name('group_members.index'); // Already filtered in controller
    Route::get('group_members/{group_member}', [GroupMemberController::class, 'show'])->name('group_members.show')->middleware('can:view-groups');
    Route::get('group_members_available', [GroupMemberController::class, 'groupsAvailable'])->name('group_members_available.show'); // Permissions handled in the controller
    Route::get('user_members_available', [GroupMemberController::class, 'usersAvailable'])->name('user_members_available.show')->middleware('can:view-groups');
    Route::post('group_members', [GroupMemberController::class, 'store'])->name('group_members.store')->middleware('can:edit-groups');
    Route::delete('group_members/{group_member}', [GroupMemberController::class, 'destroy'])->name('group_members.destroy')->middleware('can:edit-groups');

    // Environment Variables
    Route::get('environment_variables', [EnvironmentVariablesController::class, 'index'])->name('environment_variables.index')->middleware('can:view-environment_variables');
    Route::get('environment_variables/{environment_variable}', [EnvironmentVariablesController::class, 'show'])->name('environment_variables.show')->middleware('can:view-environment_variables');
    Route::post('environment_variables', [EnvironmentVariablesController::class, 'store'])->name('environment_variables.store')->middleware('can:create-environment_variables');
    Route::put('environment_variables/{environment_variable}', [EnvironmentVariablesController::class, 'update'])->name('environment_variables.update')->middleware('can:edit-environment_variables');
    Route::delete('environment_variables/{environment_variable}', [EnvironmentVariablesController::class, 'destroy'])->name('environment_variables.destroy')->middleware('can:delete-environment_variables');

    // Screens
    Route::get('screens', [ScreenController::class, 'index'])->name('screens.index'); // Permissions handled in the controller
    Route::get('screens/{screen}', [ScreenController::class, 'show'])->name('screens.show'); // Permissions handled in the controller
    Route::post('screens', [ScreenController::class, 'store'])->name('screens.store')->middleware('can:create-screens');
    Route::put('screens/{screen}', [ScreenController::class, 'update'])->name('screens.update')->middleware('can:edit-screens');
    Route::put('screens/{screen}/draft', [ScreenController::class, 'draft'])->name('screens.draft')->middleware('can:edit-screens');
    Route::post('screens/{screen}/close', [ScreenController::class, 'close'])->name('screens.close')->middleware('can:edit-screens');
    Route::put('screens/{screen}/duplicate', [ScreenController::class, 'duplicate'])->name('screens.duplicate')->middleware('can:create-screens');
    Route::delete('screens/{screen}', [ScreenController::class, 'destroy'])->name('screens.destroy')->middleware('can:delete-screens');
    Route::post('screens/{screen}/export', [ScreenController::class, 'export'])->name('screens.export')->middleware('can:export-screens');
    Route::post('screens/import', [ScreenController::class, 'import'])->name('screens.import')->middleware('can:import-screens');

    // Screen Categories
    Route::get('screen_categories', [ScreenCategoryController::class, 'index'])->name('screen_categories.index')->middleware('can:view-screen-categories');
    Route::get('screen_categories/{screen_category}', [ScreenCategoryController::class, 'show'])->name('screen_categories.show')->middleware('can:view-screen-categories');
    Route::post('screen_categories', [ScreenCategoryController::class, 'store'])->name('screen_categories.store')->middleware('can:create-screen-categories');
    Route::put('screen_categories/{screen_category}', [ScreenCategoryController::class, 'update'])->name('screen_categories.update')->middleware('can:edit-screen-categories');
    Route::delete('screen_categories/{screen_category}', [ScreenCategoryController::class, 'destroy'])->name('screen_categories.destroy')->middleware('can:delete-screen-categories');

    // Scripts
    Route::get('scripts', [ScriptController::class, 'index'])->name('scripts.index')->middleware('can:view-scripts');
    Route::get('scripts/{script}', [ScriptController::class, 'show'])->name('scripts.show')->middleware('can:view-scripts');
    Route::post('scripts', [ScriptController::class, 'store'])->name('scripts.store')->middleware('can:create-scripts');
    Route::put('scripts/{script}', [ScriptController::class, 'update'])->name('scripts.update')->middleware('can:edit-scripts');
    Route::put('scripts/{script}/draft', [ScriptController::class, 'draft'])->name('scripts.draft')->middleware('can:edit-scripts');
    Route::post('scripts/{script}/close', [ScriptController::class, 'close'])->name('scripts.close')->middleware('can:edit-scripts');
    Route::put('scripts/{script}/duplicate', [ScriptController::class, 'duplicate'])->name('scripts.duplicate')->middleware('can:create-scripts');
    Route::delete('scripts/{script}', [ScriptController::class, 'destroy'])->name('scripts.destroy')->middleware('can:delete-scripts');
    Route::post('scripts/{script}/preview', [ScriptController::class, 'preview'])->name('scripts.preview')->middleware('can:view-scripts');
    Route::post('scripts/execute/{script_id}/{script_key?}', [ScriptController::class, 'execute'])->name('scripts.execute');
    Route::get('scripts/execution/{key}', [ScriptController::class, 'execution'])->name('scripts.execution');

    // Script Categories
    Route::get('script_categories', [ScriptCategoryController::class, 'index'])->name('script_categories.index')->middleware('can:view-script-categories');
    Route::get('script_categories/{script_category}', [ScriptCategoryController::class, 'show'])->name('script_categories.show')->middleware('can:view-script-categories');
    Route::post('script_categories', [ScriptCategoryController::class, 'store'])->name('script_categories.store')->middleware('can:create-script-categories');
    Route::put('script_categories/{script_category}', [ScriptCategoryController::class, 'update'])->name('script_categories.update')->middleware('can:edit-script-categories');
    Route::delete('script_categories/{script_category}', [ScriptCategoryController::class, 'destroy'])->name('script_categories.destroy')->middleware('can:delete-script-categories');

    // Processes
    Route::get('processes', [ProcessController::class, 'index'])->name('processes.index')->middleware('can:view-processes');
    Route::get('processes/{process}', [ProcessController::class, 'show'])->name('processes.show')->middleware('can:view-processes');
    Route::post('processes/{process}/export', [ProcessController::class, 'export'])->name('processes.export')->middleware('can:export-processes');
    Route::post('processes/import', [ProcessController::class, 'import'])->name('processes.import')->middleware('can:import-processes');
    Route::post('processes/import/validation', [ProcessController::class, 'preimportValidation'])->name('processes.preimportValidation')->middleware('can:import-processes');
    Route::get('processes/import/{code}/is_ready', [ProcessController::class, 'import_ready'])->name('processes.import_is_ready')->middleware('can:import-processes');
    Route::post('processes/{process}/import/assignments', [ProcessController::class, 'importAssignments'])->name('processes.import.assignments')->middleware('can:import-processes');
    Route::post('processes', [ProcessController::class, 'store'])->name('processes.store')->middleware('can:create-processes');
    Route::put('processes/{process}', [ProcessController::class, 'update'])->name('processes.update')->middleware('can:edit-processes');
    Route::put('processes/{process}/draft', [ProcessController::class, 'updateDraft'])->name('processes.update_draft')->middleware('can:edit-screens');
    Route::post('processes/{process}/close', [ProcessController::class, 'close'])->name('processes.close')->middleware('can:edit-processes');
    Route::delete('processes/{process}', [ProcessController::class, 'destroy'])->name('processes.destroy')->middleware('can:archive-processes');
    Route::put('processes/{processId}/restore', [ProcessController::class, 'restore'])->name('processes.restore')->middleware('can:archive-processes');

    // Process Categories
    Route::get('process_categories', [ProcessCategoryController::class, 'index'])->name('process_categories.index')->middleware('can:view-process-categories');
    Route::get('process_categories/{process_category}', [ProcessCategoryController::class, 'show'])->name('process_categories.show')->middleware('can:view-process-categories');
    Route::post('process_categories', [ProcessCategoryController::class, 'store'])->name('process_categories.store')->middleware('can:create-process-categories');
    Route::put('process_categories/{process_category}', [ProcessCategoryController::class, 'update'])->name('process_categories.update')->middleware('can:edit-process-categories');
    Route::delete('process_categories/{process_category}', [ProcessCategoryController::class, 'destroy'])->name('process_categories.destroy')->middleware('can:delete-process-categories');

    // Permissions
    Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
    Route::put('permissions', [PermissionController::class, 'update'])->name('permissions.update')->middleware('can:edit-users');

    // Tasks
    Route::get('tasks', [TaskController::class, 'index'])->name('tasks.index'); // Already filtered in controller
    Route::get('tasks/{task}', [TaskController::class, 'show'])->name('tasks.show')->middleware('can:view,task');
    Route::get('tasks/{task}/screens/{screen}', [TaskController::class, 'getScreen'])->name('tasks.get_screen')->middleware('can:viewScreen,task,screen');

    // Requests
    Route::get('requests', [ProcessRequestController::class, 'index'])->name('requests.index'); // Already filtered in controller
    Route::get('requests/{request}', [ProcessRequestController::class, 'show'])->name('requests.show')->middleware('can:view,request');
    Route::put('requests/{request}', [ProcessRequestController::class, 'update'])->name('requests.update')->middleware('can:update,request');
    Route::put('requests/{request}/retry', [ProcessRequestController::class, 'retry'])->name('requests.retry')->middleware('can:update,request');
    Route::delete('requests/{request}', [ProcessRequestController::class, 'destroy'])->name('requests.destroy')->middleware('can:destroy,request');

    // Request Files
    Route::get('requests/{request}/files', [ProcessRequestFileController::class, 'index'])->name('requests.files.index')->middleware('can:view,request');
    Route::get('requests/{request}/files/{file}', [ProcessRequestFileController::class, 'show'])->name('requests.files.show')->middleware('can:view,request');
    Route::post('requests/{request}/files', [ProcessRequestFileController::class, 'store'])->name('requests.files.store')->middleware('can:participate,request');
    Route::delete('requests/{request}/files/{file}', [ProcessRequestFileController::class, 'destroy'])->name('requests.filesrequests.files.destroy')->middleware('can:participate,request');

    // Files
    Route::get('files', [FileController::class, 'index'])->name('files.index')->middleware('can:view-files');
    Route::get('files/{file}', [FileController::class, 'show'])->name('files.show')->middleware('can:view,file');
    Route::get('files/{file}/contents', [FileController::class, 'download'])->name('files.download')->middleware('can:view,file');
    Route::post('files', [FileController::class, 'store'])->name('files.store')->middleware('can:create,ProcessMaker\Models\Media');
    Route::put('files/{file}', [FileController::class, 'update'])->name('files.update')->middleware('can:update,file');
    Route::delete('files/{file}', [FileController::class, 'destroy'])->name('files.destroy')->middleware('can:delete,file');

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');  // Already filtered in controller
    Route::get('notifications/{notification}', [NotificationController::class, 'show'])->name('notifications.show')->middleware('can:view,notification');
    Route::post('notifications', [NotificationController::class, 'store'])->name('notifications.store')->middleware('can:create,ProcessMaker\Models\Notification');
    Route::put('notifications/{notification}', [NotificationController::class, 'update'])->name('notifications.update')->middleware('can:edit,notification');
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy')->middleware('can:delete,notification');

    // Mark Notifications as Read & Unread
    Route::put('read_notifications', [NotificationController::class, 'updateAsRead'])->name('notifications.update_as_read'); // No permissions necessary
    Route::put('unread_notifications', [NotificationController::class, 'updateAsUnread'])->name('notifications.update_as_unread'); // No permissions necessary
    Route::put('read_all_notifications', [NotificationController::class, 'updateAsReadAll'])->name('notifications.update_all_as_read'); // No permissions necessary

    // Task Assignments
    Route::get('task_assignments', [TaskAssignmentController::class, 'index'])->name('task_assignments.index')->middleware('can:view-task_assignments');
    Route::post('task_assignments', [TaskAssignmentController::class, 'store'])->name('task_assignments.store')->middleware('can:create-task_assignments');
    Route::put('task_assignments/{task_assignment}', [TaskAssignmentController::class, 'update'])->name('task_assignments.update')->middleware('can:edit-task_assignments');
    Route::delete('task_assignments/{task_assignment}', [TaskAssignmentController::class, 'destroy'])->name('task_assignments.destroy')->middleware('can:delete-task_assignments');

    // Comments
    Route::get('comments', [CommentController::class, 'index'])->name('comments.index')->middleware('can:view-comments');
    Route::get('comments/{comment}', [CommentController::class, 'show'])->name('comments.show')->middleware('can:view-comments');
    Route::post('comments', [CommentController::class, 'store'])->name('comments.store')->middleware('can:create-comments');
    Route::put('comments/{comment}', [CommentController::class, 'update'])->name('comments.update')->middleware('can:edit-comments');
    Route::delete('comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy')->middleware('can:delete-comments');

    // Global signals
    Route::get('signals', [SignalController::class, 'index'])->name('signals.index')->middleware('can:view-signals');
    Route::get('signals/{signalId}', [SignalController::class, 'show'])->name('signals.show')->middleware('can:view-signals');
    Route::post('signals', [SignalController::class, 'store'])->name('signals.store')->middleware('can:create-signals');
    Route::put('signals/{signalId}', [SignalController::class, 'update'])->name('signals.update')->middleware('can:edit-signals');
    Route::delete('signals/{signalId}', [SignalController::class, 'destroy'])->name('signals.destroy')->middleware('can:delete-signals');

    // UI customization
    Route::post('customize-ui', [CssOverrideController::class, 'store'])->name('customize-ui.store');

    // Rebuild Script Executors
    Route::get('script-executors', [ScriptExecutorController::class, 'index'])->name('script-executors.index');
    Route::get('script-executors/available-languages', [ScriptExecutorController::class, 'availableLanguages'])->name('script-executors.available-languages');
    Route::put('script-executors/{script_executor}', [ScriptExecutorController::class, 'update'])->name('script-executors.update');
    Route::post('script-executors', [ScriptExecutorController::class, 'store'])->name('script-executors.store');
    Route::post('script-executors/cancel', [ScriptExecutorController::class, 'cancel'])->name('script-executors.cancel');
    Route::delete('script-executors/{script_executor}', [ScriptExecutorController::class, 'delete'])->name('script-executors.delete');

    // Security logs
    Route::get('security-logs', [SecurityLogController::class, 'index'])->name('security-logs.index')->middleware('can:view-security-logs');
    Route::get('security-logs/{securityLog}', [SecurityLogController::class, 'show'])->name('security-logs.show')->middleware('can:view-security-logs');

    // Settings
    Route::get('settings', [SettingController::class, 'index'])->name('settings.index')->middleware('can:view-settings');
    Route::get('settings/groups', [SettingController::class, 'groups'])->name('settings.groups')->middleware('can:view-settings');
    Route::post('settings/import', [SettingController::class, 'import'])->name('settings.import')->middleware('can:update-settings');
    Route::put('settings/{setting}', [SettingController::class, 'update'])->name('settings.update')->middleware('can:update-settings');
    Route::get('settings/group/{group}/buttons', [SettingController::class, 'buttons'])->name('settings.buttons')->middleware('can:view-settings')->where('group', '[A-Za-z0-9 -_]+');
    Route::post('settings/upload-file', [SettingController::class, 'upload'])->name('settings.upload-file')->middleware('can:update-settings');

    // Import & Export
    Route::get('export/manifest/{type}/{id}/', [ExportController::class, 'manifest'])->name('export.manifest')->middleware('can:export-processes');
    Route::post('export/{type}/download/{id}', [ExportController::class, 'download'])->name('export.download')->middleware('template-authorization');
    Route::post('import/preview', [ImportController::class, 'preview'])->name('import.preview')->middleware('can:export-processes');
    Route::post('import/do-import', [ImportController::class, 'import'])->name('import.do_import')->middleware('can:import-processes');

    // Templates
    Route::get('templates/{type}', [TemplateController::class, 'index'])->name('template.index')->middleware('template-authorization');
    Route::post('template/{type}/{id}', [TemplateController::class, 'store'])->name('template.store')->middleware('template-authorization');
    Route::post('template/create/{type}/{id}', [TemplateController::class, 'create'])->name('template.create')->middleware('template-authorization');
    Route::put('template/{type}/{processId}', [TemplateController::class, 'updateTemplateManifest'])->name('template.update')->middleware('template-authorization');
    Route::put('template/{type}/{id}', [TemplateController::class, 'updateTemplate'])->name('template.update.template')->middleware('template-authorization');
    Route::put('template/settings/{type}/{id}', [TemplateController::class, 'updateTemplateConfigs'])->name('template.settings.update')->middleware('template-authorization');
    Route::delete('template/{type}/{id}', [TemplateController::class, 'delete'])->name('template.delete')->middleware('template-authorization');
    Route::get('modeler/templates/{type}/{id}', [TemplateController::class, 'show'])->name('modeler.template.show')->middleware('template-authorization');
    Route::post('template/do-import/{type}', [ImportController::class, 'importTemplate'])->name('import.do_importTemplate')->middleware('template-authorization');
    Route::post('templates/{type}/import/validation', [TemplateController::class, 'preImportValidation'])->name('template.preImportValidation')->middleware('template-authorization');

    // debugging javascript errors
    Route::post('debug', [DebugController::class, 'store'])->name('debug.store')->middleware('throttle');

    // Returns a json error message instead of HTML
    Route::fallback(function () {
        return response()->json(['error' => 'Not Found'], 404);
    })->name('fallback');

    Route::get('/test_acknowledgement', [TestStatusController::class, 'testAcknowledgement'])->name('test.acknowledgement');

    // OpenAI
    Route::middleware('throttle:30,1')->post('openai/nlq-to-pmql', [OpenAIController::class, 'NLQToPMQL'])->name('openai.nlq-to-pmql');
    Route::middleware('throttle:30,1')->post('openai/nlq-to-category', [OpenAIController::class, 'NLQToCategory'])->name('openai.nlq-to-category');
    Route::get('openai/recent-searches', [OpenAIController::class, 'recentSearches'])->name('openai.recent-searches');
    Route::delete('openai/recent-searches', [OpenAIController::class, 'deleteRecentSearches'])->name('openai.recent-searches.delete');
});
