<?php

use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Foundation\Configuration\Middleware;
use ProcessMaker\Application;
use ProcessMaker\Console\Kernel as ConsoleKernel;
use ProcessMaker\Exception\Handler;
use ProcessMaker\Http\Kernel as HttpKernel;
use ProcessMaker\Http\Middleware as ProcessMakerMiddleware;
use ProcessMaker\Http\Middleware\ServerTimingMiddleware;

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

$app = Application::configure(basePath: realpath(__DIR__ . '/../'))
    ->withMiddleware(function (Middleware $middleware) {
        // Global middleware - preserve order by appending in sequence
        $middleware->append(ProcessMakerMiddleware\TrimStrings::class);
        $middleware->append(ProcessMakerMiddleware\TrustHosts::class);
        $middleware->append(ProcessMakerMiddleware\TrustProxies::class);
        $middleware->append(ProcessMakerMiddleware\BrowserCache::class);
        $middleware->append(ServerTimingMiddleware::class);
        $middleware->append(ProcessMakerMiddleware\FileSizeCheck::class);
        $middleware->append(ProcessMakerMiddleware\AddTenantHeaders::class);
        $middleware->append(ProcessMakerMiddleware\HideServerHeaders::class);

        // Set middleware priority - IgnoreMapFiles must run before Authenticate
        $middleware->priority([
            ProcessMakerMiddleware\IgnoreMapFiles::class,
            ProcessMakerMiddleware\ProcessMakerAuthenticate::class,
        ]);

        // Web middleware group
        // Note: Laravel defaults (StartSession, AddQueuedCookiesToResponse, ShareErrorsFromSession, SubstituteBindings)
        // and Passport (CreateFreshApiToken) are automatically included
        $middleware->prependToGroup('web', [
            ProcessMakerMiddleware\EncryptCookies::class,
        ]);
        $middleware->appendToGroup('web', [
            ProcessMakerMiddleware\SessionStarted::class,
            ProcessMakerMiddleware\AuthenticateSession::class,
            ProcessMakerMiddleware\SessionControlKill::class,
            ProcessMakerMiddleware\SetLocale::class,
            ProcessMakerMiddleware\GenerateMenus::class,
            ProcessMakerMiddleware\IgnoreMapFiles::class,
        ]);

        // API middleware group
        $middleware->appendToGroup('api', [
            ProcessMakerMiddleware\LaravelTokenMiddleware::class,
        ]);

        // Engine middleware group
        $middleware->appendToGroup('engine', [
            'auth:api',
            'setlocale',
            'bindings',
            'sanitize',
        ]);

        // Middleware aliases (custom only - Laravel defaults are already registered)
        $middleware->alias([
            'auth' => ProcessMakerMiddleware\ProcessMakerAuthenticate::class,
            'bindings' => Illuminate\Routing\Middleware\SubstituteBindings::class,
            'can' => ProcessMakerMiddleware\CustomAuthorize::class,
            'force_change_password' => ProcessMakerMiddleware\VerifyChangePasswordNeeded::class,
            'guest' => ProcessMakerMiddleware\RedirectIfAuthenticated::class,
            'permission' => ProcessMakerMiddleware\PermissionCheck::class,
            'sanitize' => ProcessMakerMiddleware\SanitizeInput::class,
            'setlocale' => ProcessMakerMiddleware\SetLocale::class,
            'setskin' => ProcessMakerMiddleware\SetSkin::class,
            'template-authorization' => ProcessMakerMiddleware\TemplateAuthorization::class,
            'edit_username_password' => ProcessMakerMiddleware\ValidateEditUserAndPasswordPermission::class,
            '2fa' => ProcessMakerMiddleware\TwoFactorAuthentication::class,
            'saml_request' => ProcessMakerMiddleware\SamlRequest::class,
            'session_block' => ProcessMakerMiddleware\SessionControlBlock::class,
            'session_kill' => ProcessMakerMiddleware\SessionControlKill::class,
            'no-cache' => ProcessMakerMiddleware\NoCache::class,
            'admin' => ProcessMakerMiddleware\IsAdmin::class,
            'manager' => ProcessMakerMiddleware\IsManager::class,
            'etag' => ProcessMakerMiddleware\Etag\HandleEtag::class,
            'file_size_check' => ProcessMakerMiddleware\FileSizeCheck::class,
        ]);
    })
    ->create();

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/

$app->singleton(
    HttpKernelContract::class,
    HttpKernel::class
);

$app->singleton(
    ConsoleKernelContract::class,
    ConsoleKernel::class
);

$app->singleton(
    ExceptionHandler::class,
    Handler::class
);

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

return $app;
