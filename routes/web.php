<?php

use Illuminate\Support\Facades\Route;
use ProcessMaker\Http\Controllers\AboutController;
use ProcessMaker\Http\Controllers\Admin\AuthClientController;
use ProcessMaker\Http\Controllers\Admin\CssOverrideController;
use ProcessMaker\Http\Controllers\Admin\GroupController;
use ProcessMaker\Http\Controllers\Admin\LdapLogsController;
use ProcessMaker\Http\Controllers\Admin\QueuesController;
use ProcessMaker\Http\Controllers\Admin\ScriptExecutorController;
use ProcessMaker\Http\Controllers\Admin\SettingsController;
use ProcessMaker\Http\Controllers\Admin\UserController;
use ProcessMaker\Http\Controllers\AdminController;
use ProcessMaker\Http\Controllers\Auth\ChangePasswordController;
use ProcessMaker\Http\Controllers\Auth\ClientController;
use ProcessMaker\Http\Controllers\Auth\ForgotPasswordController;
use ProcessMaker\Http\Controllers\Auth\LoginController;
use ProcessMaker\Http\Controllers\Auth\ResetPasswordController;
use ProcessMaker\Http\Controllers\Auth\TwoFactorAuthController;
use ProcessMaker\Http\Controllers\Designer\DesignerController;
use ProcessMaker\Http\Controllers\HomeController;
use ProcessMaker\Http\Controllers\InboxRulesController;
use ProcessMaker\Http\Controllers\NotificationController;
use ProcessMaker\Http\Controllers\Process\EnvironmentVariablesController;
use ProcessMaker\Http\Controllers\Process\ModelerController;
use ProcessMaker\Http\Controllers\Process\ProcessTranslationController;
use ProcessMaker\Http\Controllers\Process\ScreenBuilderController;
use ProcessMaker\Http\Controllers\Process\ScreenController;
use ProcessMaker\Http\Controllers\Process\ScriptController;
use ProcessMaker\Http\Controllers\Process\SignalController;
use ProcessMaker\Http\Controllers\ProcessController;
use ProcessMaker\Http\Controllers\ProcessesCatalogueController;
use ProcessMaker\Http\Controllers\ProfileController;
use ProcessMaker\Http\Controllers\RequestController;
use ProcessMaker\Http\Controllers\Saml\MetadataController;
use ProcessMaker\Http\Controllers\TaskController;
use ProcessMaker\Http\Controllers\TemplateController;
use ProcessMaker\Http\Controllers\TestStatusController;
use ProcessMaker\Http\Controllers\UnavailableController;
use ProcessMaker\Http\Middleware\NoCache;

Route::middleware('auth', 'session_kill', 'sanitize', 'force_change_password', '2fa')->group(function () {
    // Routes related to Authentication (password reset, etc)
    // Auth::routes();
    Route::prefix('admin')->group(function () {
        Route::get('queues', [QueuesController::class, 'index'])->name('queues.index');
        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index')->middleware('can:view-settings');
        Route::get('ldap-logs', [LdapLogsController::class, 'index'])->name('ldap.index')->middleware('can:view-settings');
        Route::get('settings/export', [SettingsController::class, 'export'])->name('settings.export')->middleware('can:view-settings');
        Route::get('groups', [GroupController::class, 'index'])->name('groups.index')->middleware('can:view-groups');
        // Route::get('groups/{group}', [GroupController::class, 'show'])->name('groups.show')->middleware('can:show-groups,group');
        Route::get('groups/{group}/edit', [GroupController::class, 'edit'])->name('groups.edit')->middleware('can:edit-groups,group');

        Route::get('users', [UserController::class, 'index'])->name('users.index')->middleware('can:view-users');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit')->middleware('can:edit-users,user');

        Route::get('auth-clients', [AuthClientController::class, 'index'])->name('auth-clients.index')->middleware('can:view-auth_clients');

        Route::get('customize-ui/{tab?}', [CssOverrideController::class, 'edit'])->name('customize-ui.edit');

        Route::get('script-executors', [ScriptExecutorController::class, 'index'])->name('script-executors.index');

        // temporary, should be removed
        Route::get('security-logs/download/all', [ProcessMaker\Http\Controllers\Api\SecurityLogController::class, 'downloadForAllUsers'])->middleware('can:view-security-logs');
        Route::get('security-logs/download/{user}', [ProcessMaker\Http\Controllers\Api\SecurityLogController::class, 'downloadForUser'])->middleware('can:view-security-logs');
    });

    Route::get('admin', [AdminController::class, 'index'])->name('admin.index');

    Route::prefix('designer')->group(function () {
        Route::get('environment-variables', [EnvironmentVariablesController::class, 'index'])->name('environment-variables.index')->middleware('can:view-environment_variables');
        Route::get('environment-variables/{environment_variable}/edit', [EnvironmentVariablesController::class, 'edit'])->name('environment-variables.edit')->middleware('can:edit-environment_variables,environment_variable ');

        Route::get('screens', [ScreenController::class, 'index'])->name('screens.index')->middleware('can:view-screens');
        Route::get('screens/{screen}/edit', [ScreenController::class, 'edit'])->name('screens.edit')->middleware('can:edit-screens,screen');
        Route::get('screens/{screen}/export', [ScreenController::class, 'export'])->name('screens.export')->middleware('can:export-screens');
        Route::get('screens/import', [ScreenController::class, 'import'])->name('screens.import')->middleware('can:import-screens');
        Route::get('screens/{screen}/download/{key}', [ScreenController::class, 'download'])->name('screens.download')->middleware('can:export-screens');
        Route::get('screen-builder/{screen}/edit/{processId?}', [ScreenBuilderController::class, 'edit'])->name('screen-builder.edit')->middleware('can:edit-screens,screen');
        Route::get('screens/preview', [ScreenController::class, 'preview'])->name('screens.preview')->middleware('can:view-screens');

        Route::get('scripts', [ScriptController::class, 'index'])->name('scripts.index')->middleware('can:view-scripts');
        Route::get('scripts/{script}/edit', [ScriptController::class, 'edit'])->name('scripts.edit')->middleware('can:edit-scripts,script');
        Route::get('scripts/{script}/builder/{processId?}', [ScriptController::class, 'builder'])->name('scripts.builder')->middleware('can:edit-scripts,script');
        Route::get('scripts/preview', [ScriptController::class, 'preview'])->name('scripts.preview')->middleware('can:view-screens');

        Route::get('signals', [SignalController::class, 'index'])->name('signals.index')->middleware('can:view-signals');
        Route::get('signals/{signalId}/edit', [SignalController::class, 'edit'])->name('signals.edit')->middleware('can:edit-signals');
    });

    Route::get('designer/processes/categories', [ProcessController::class, 'index'])->name('process-categories.index')->middleware('can:view-process-categories');

    Route::get('designer/screens/categories', [ScreenController::class, 'index'])->name('screen-categories.index')->middleware('can:view-screen-categories');

    Route::get('designer/scripts/categories', [ScriptController::class, 'index'])->name('script-categories.index')->middleware('can:view-script-categories');
    Route::get('designer', [DesignerController::class, 'index'])->name('designer.index');

    Route::get('process-browser/{process?}', [ProcessesCatalogueController::class, 'index'])
       ->name('process.browser.index')
       ->middleware('can:view-process-catalog');
    //------------------------------------------------------------------------------------------
    // Below route is for backward compatibility with old format routes. PLEASE DO NOT REMOVE
    //------------------------------------------------------------------------------------------
    Route::get('processes-catalogue/{process?}', function ($process = null) {
        return redirect()->route('process.browser.index', [$process]);
    })->name('processes.catalogue.index');
    //------------------------------------------------------------------------------------------

    Route::get('processes', [ProcessController::class, 'index'])->name('processes.index');
    Route::get('processes/{process}/edit', [ProcessController::class, 'edit'])->name('processes.edit')->middleware('can:edit-processes');
    Route::get('processes/{process}/export/{page?}', [ProcessController::class, 'export'])->name('processes.export')->middleware('can:export-processes');
    Route::get('processes/import/{page?}', [ProcessController::class, 'import'])->name('processes.import')->middleware('can:import-processes');
    Route::get('import/download-debug', [ProcessController::class, 'downloadImportDebug'])->name('import.download-debug')->middleware('can:import-processes');
    Route::get('processes/{process}/download/{key}', [ProcessController::class, 'download'])->name('processes.download')->middleware('can:export-processes');
    Route::get('processes/create', [ProcessController::class, 'create'])->name('processes.create')->middleware('can:create-processes');
    Route::post('processes', [ProcessController::class, 'store'])->name('processes.store')->middleware('can:edit-processes');
    Route::get('processes/{process}', [ProcessController::class, 'show'])->name('processes.show')->middleware('can:view-processes');
    Route::put('processes/{process}', [ProcessController::class, 'update'])->name('processes.update')->middleware('can:edit-processes');
    Route::delete('processes/{process}', [ProcessController::class, 'destroy'])->name('processes.destroy')->middleware('can:archive-processes');

    Route::get('process_events/{process}', [ProcessController::class, 'triggerStartEventApi'])->name('process_events.trigger')->middleware('can:start,process');
    Route::get('processes/{process}/export/translation/{language}', [ProcessTranslationController::class, 'export']);
    Route::get('processes/{process}/import/translation', [ProcessTranslationController::class, 'import']);

    Route::get('about', [AboutController::class, 'index'])->name('about.index');

    Route::get('profile/edit', [ProfileController::class, 'edit'])->name('profile.edit')->middleware('can:edit-personal-profile');
    Route::get('profile/{id}', [ProfileController::class, 'show'])->name('profile.show');
    // Ensure our modeler loads at a distinct url
    Route::get('modeler/{process}', [ModelerController::class, 'show'])->name('modeler.show')->middleware('can:edit,process');
    Route::get('modeler/{process}/inflight/{request?}', [ModelerController::class, 'inflight'])->name('modeler.inflight')->middleware('can:view,request');

    Route::get('/', [HomeController::class, 'index'])->name('home');

    Route::post('/keep-alive', [LoginController::class, 'keepAlive'])->name('keep-alive');

    Route::get('requests/search', [RequestController::class, 'search'])->name('requests.search');
    Route::get('requests/{type?}', [RequestController::class, 'index'])
        ->where('type', 'all|in_progress|completed')
        ->name('requests_by_type')
        ->middleware('no-cache');
    Route::get('request/{request}/files/{media}', [RequestController::class, 'downloadFiles'])->middleware('can:view,request');
    Route::get('requests', [RequestController::class, 'index'])
        ->name('requests.index')
        ->middleware('no-cache');
    Route::get('requests/{request}', [RequestController::class, 'show'])->name('requests.show');
    Route::get('requests/mobile/{request}', [RequestController::class, 'show'])->name('requests.showMobile');
    Route::get('requests/{request}/task/{task}/screen/{screen}', [RequestController::class, 'screenPreview'])->name('requests.screen-preview');

    Route::get('tasks/search', [TaskController::class, 'search'])->name('tasks.search');
    Route::get('tasks', [TaskController::class, 'index'])
        ->name('tasks.index')
        ->middleware('no-cache');
    Route::get('tasks/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
    Route::get('tasks/{task}/edit/{preview}', [TaskController::class, 'edit'])->name('tasks.preview');

    Route::get('tasks/rules/{path?}', [InboxRulesController::class, 'index'])->name('inbox-rules.index')->where('path', '.*');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');

    Route::get('template/{type}/import', [TemplateController::class, 'import'])->name('templates.import')->middleware('template-authorization');
    Route::get('template/{type}/{template}/configure', [TemplateController::class, 'configure'])->name('templates.configure')->middleware('template-authorization');
    Route::get('template/assets', [TemplateController::class, 'chooseTemplateAssets'])->name('templates.assets');
    Route::get('modeler/templates/{id}', [TemplateController::class, 'show'])->name('modeler.template.show')->middleware('template-authorization', 'can:edit-process-templates');
    Route::get('screen-template/{screen}/export', [TemplateController::class, 'export'])->name('screens.export')->middleware('can:export-screens');
    Route::get('screen-template/import', [TemplateController::class, 'importScreen'])->name('screens.importScreen')->middleware('can:import-screens');
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
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'loginWithIntendedCheck']);
Route::get('logout', [LoginController::class, 'beforeLogout'])->name('logout');
Route::get('2fa', [TwoFactorAuthController::class, 'displayTwoFactorAuthForm'])->name('2fa');
Route::post('2fa/validate', [TwoFactorAuthController::class, 'validateTwoFactorAuthCode'])->name('2fa.validate');
Route::get('2fa/send_again', [TwoFactorAuthController::class, 'sendCode'])->name('2fa.send_again');
Route::get('2fa/auth_app_qr', [TwoFactorAuthController::class, 'displayAuthAppQr'])->name('2fa.auth_app_qr');

// Password Reset Routes...
Route::get('password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('password/reset', [ResetPasswordController::class, 'reset']);
Route::get('password/change', [ChangePasswordController::class, 'edit'])->name('password.change');

// overwrite laravel passport
Route::get('oauth/clients', [ClientController::class, 'index'])->name('passport.clients.index')->middleware('can:view-auth_clients');
Route::get('oauth/clients/{client_id}', [ClientController::class, 'show'])->name('passport.clients.show')->middleware('can:view-auth_clients');
Route::post('oauth/clients', [ClientController::class, 'store'])->name('passport.clients.store')->middleware('can:create-auth_clients');
Route::put('oauth/clients/{client_id}', [ClientController::class, 'update'])->name('passport.clients.update')->middleware('can:edit-auth_clients');
Route::delete('oauth/clients/{client_id}', [ClientController::class, 'destroy'])->name('passport.clients.delete')->middleware('can:delete-auth_clients');

Route::get('password/success', function () {
    return view('auth.passwords.success', ['title' => __('Password Reset')]);
})->name('password-success');

Route::get('/unavailable', [UnavailableController::class, 'show'])->name('error.unavailable');

// SAML Metadata Route
Route::resource('/saml/metadata', MetadataController::class)->only('index');
