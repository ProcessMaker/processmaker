<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Process;
use App\Http\Controllers\ProcessController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TestStatusController;
use App\Http\Controllers\UnavailableController;
use Illuminate\Support\Facades\Route;
use ProcessMaker\Http\Controllers\Api\Requests\RequestsController;

Route::middleware('auth', 'sanitize', 'external.connection', 'force_change_password')->group(function () {

    // Routes related to Authentication (password reset, etc)
    // Auth::routes();
    Route::prefix('admin')->group(function () {
        Route::get('queues', [Admin\QueuesController::class, 'index'])->name('queues.index');
        Route::get('settings', [Admin\SettingsController::class, 'index'])->name('settings.index')->middleware('can:view-settings');
        Route::get('ldap-logs', [Admin\LdapLogsController::class, 'index'])->name('ldap.index')->middleware('can:view-settings');
        Route::get('settings/export', [Admin\SettingsController::class, 'export'])->name('settings.export')->middleware('can:view-settings');
        Route::get('groups', [Admin\GroupController::class, 'index'])->name('groups.index')->middleware('can:view-groups');
        // Route::get('groups/{group}', [Admin\GroupController::class, 'show'])->name('groups.show')->middleware('can:show-groups,group');
        Route::get('groups/{group}/edit', [Admin\GroupController::class, 'edit'])->name('groups.edit')->middleware('can:edit-groups,group');

        Route::get('users', [Admin\UserController::class, 'index'])->name('users.index')->middleware('can:view-users');
        Route::get('users/{user}/edit', [Admin\UserController::class, 'edit'])->name('users.edit')->middleware('can:edit-users,user');

        Route::get('auth-clients', [Admin\AuthClientController::class, 'index'])->name('auth-clients.index')->middleware('can:view-auth_clients');

        Route::get('customize-ui/{tab?}', [Admin\CssOverrideController::class, 'edit'])->name('customize-ui.edit');

        Route::get('script-executors', [Admin\ScriptExecutorController::class, 'index'])->name('script-executors.index');
    });

    Route::get('admin', [AdminController::class, 'index'])->name('admin.index');

    Route::prefix('designer')->group(function () {
        Route::get('environment-variables', [Process\EnvironmentVariablesController::class, 'index'])->name('environment-variables.index')->middleware('can:view-environment_variables');
        Route::get('environment-variables/{environment_variable}/edit', [Process\EnvironmentVariablesController::class, 'edit'])->name('environment-variables.edit')->middleware('can:edit-environment_variables,environment_variable ');

        Route::get('screens', [Process\ScreenController::class, 'index'])->name('screens.index')->middleware('can:view-screens');
        Route::get('screens/{screen}/edit', [Process\ScreenController::class, 'edit'])->name('screens.edit')->middleware('can:edit-screens,screen');
        Route::get('screens/{screen}/export', [Process\ScreenController::class, 'export'])->name('screens.export')->middleware('can:export-screens');
        Route::get('screens/import', [Process\ScreenController::class, 'import'])->name('screens.import')->middleware('can:import-screens');
        Route::get('screens/{screen}/download/{key}', [Process\ScreenController::class, 'download'])->name('screens.download')->middleware('can:export-screens');
        Route::get('screen-builder/{screen}/edit', [Process\ScreenBuilderController::class, 'edit'])->name('screen-builder.edit')->middleware('can:edit-screens,screen');

        Route::get('scripts', [Process\ScriptController::class, 'index'])->name('scripts.index')->middleware('can:view-scripts');
        Route::get('scripts/{script}/edit', [Process\ScriptController::class, 'edit'])->name('scripts.edit')->middleware('can:edit-scripts,script');
        Route::get('scripts/{script}/builder', [Process\ScriptController::class, 'builder'])->name('scripts.builder')->middleware('can:edit-scripts,script');

        Route::get('signals', [Process\SignalController::class, 'index'])->name('signals.index')->middleware('can:view-signals');
        Route::get('signals/{signalId}/edit', [Process\SignalController::class, 'edit'])->name('signals.edit')->middleware('can:edit-signals');
    });

    Route::get('designer/processes/categories', [ProcessController::class, 'index'])->name('process-categories.index')->middleware('can:view-process-categories');

    Route::get('designer/screens/categories', [Process\ScreenController::class, 'index'])->name('screen-categories.index')->middleware('can:view-screen-categories');

    Route::get('designer/scripts/categories', [Process\ScriptController::class, 'index'])->name('script-categories.index')->middleware('can:view-script-categories');

    Route::get('processes', [ProcessController::class, 'index'])->name('processes.index');
    Route::get('processes/{process}/edit', [ProcessController::class, 'edit'])->name('processes.edit')->middleware('can:edit-processes');
    Route::get('processes/{process}/export', [ProcessController::class, 'export'])->name('processes.export')->middleware('can:export-processes');
    Route::get('processes/import', [ProcessController::class, 'import'])->name('processes.import')->middleware('can:import-processes');
    Route::get('processes/{process}/download/{key}', [ProcessController::class, 'download'])->name('processes.download')->middleware('can:export-processes');
    Route::get('processes/create', [ProcessController::class, 'create'])->name('processes.create')->middleware('can:create-processes');
    Route::post('processes', [ProcessController::class, 'store'])->name('processes.store')->middleware('can:edit-processes');
    Route::get('processes/{process}', [ProcessController::class, 'show'])->name('processes.show')->middleware('can:view-processes');
    Route::put('processes/{process}', [ProcessController::class, 'update'])->name('processes.edit')->middleware('can:edit-processes');
    Route::delete('processes/{process}', [ProcessController::class, 'destroy'])->name('processes.destroy')->middleware('can:archive-processes');

    Route::get('process_events/{process}', [ProcessController::class, 'triggerStartEventApi'])->middleware('can:start,process');

    Route::get('about', [AboutController::class, 'index'])->name('about.index');

    Route::get('profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('profile/{id}', [ProfileController::class, 'show'])->name('profile.show');
    // Ensure our modeler loads at a distinct url
    Route::get('modeler/{process}', 'Process\ModelerController')->name('modeler.show')->middleware('can:edit-processes');

    Route::get('/', [HomeController::class, 'index'])->name('home');

    Route::post('/keep-alive', [Auth\LoginController::class, 'keepAlive'])->name('keep-alive');

    Route::get('requests/search', [RequestController::class, 'search'])->name('requests.search');
    Route::get('requests/{type?}', [RequestController::class, 'index'])
        ->where('type', 'all|in_progress|completed')
        ->name('requests_by_type');
    Route::get('request/{request}/files/{media}', [RequestController::class, 'downloadFiles'])->middleware('can:view,request');
    Route::get('requests', [RequestController::class, 'index'])->name('requests.index');
    Route::get('requests/{request}', [RequestController::class, 'show'])->name('requests.show');
    Route::get('requests/{request}/task/{task}/screen/{screen}', [RequestController::class, 'screenPreview'])->name('requests.screen-preview');

    Route::get('tasks/search', [TaskController::class, 'search'])->name('tasks.search');
    Route::get('tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::get('tasks/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index')->middleware('can:view-notifications,notification');

    // Allows for a logged in user to see navigation on a 404 page
    Route::fallback(function () {
        return response()->view('errors.404', [], 404);
    })->name('fallback');

    Route::get('/test_status', [TestStatusController::class, 'test'])->name('test.status');
    Route::get('/test_email', [TestStatusController::class, 'email'])->name('test.email');
});

// Add our broadcasting routes
Broadcast::routes();

// Authentication Routes...
Route::get('login', [Auth\LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [Auth\LoginController::class, 'loginWithIntendedCheck']);
Route::get('logout', [Auth\LoginController::class, 'logout'])->name('logout');

// Password Reset Routes...
Route::get('password/reset', [Auth\ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('password/email', [Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('password/reset/{token}', [Auth\ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('password/reset', [Auth\ResetPasswordController::class, 'reset']);
Route::get('password/change', [Auth\ChangePasswordController::class, 'edit'])->name('password.change');

//overwrite laravel passport
Route::get('oauth/clients', [Auth\ClientController::class, 'index'])->name('passport.clients.index')->middleware('can:view-auth_clients');
Route::get('oauth/clients/{client_id}', [Auth\ClientController::class, 'show'])->name('passport.clients.show')->middleware('can:view-auth_clients');
Route::post('oauth/clients', [Auth\ClientController::class, 'store'])->name('passport.clients.store')->middleware('can:create-auth_clients');
Route::put('oauth/clients/{client_id}', [Auth\ClientController::class, 'update'])->name('passport.clients.update')->middleware('can:edit-auth_clients');
Route::delete('oauth/clients/{client_id}', [Auth\ClientController::class, 'destroy'])->name('passport.clients.update')->middleware('can:delete-auth_clients');

Route::get('password/success', function () {
    return view('auth.passwords.success', ['title' => __('Password Reset')]);
})->name('password-success');

Route::get('/unavailable', [UnavailableController::class, 'show'])->name('error.unavailable');
