<?php

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Passport\Http\Middleware\CreateFreshApiToken;
use ProcessMaker\Application;
use ProcessMaker\Console\Kernel as ConsoleKernel;
use ProcessMaker\Exception\Handler;
use ProcessMaker\Http\Kernel as HttpKernel;
use ProcessMaker\Http\Middleware as ProcessMakerMiddleware;

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
        // Replace Laravel default middleware with custom implementations
        $middleware->replace(
            Illuminate\Foundation\Http\Middleware\TrimStrings::class,
            ProcessMakerMiddleware\TrimStrings::class
        );
        $middleware->replace(
            Illuminate\Http\Middleware\TrustHosts::class,
            ProcessMakerMiddleware\TrustHosts::class
        );
        $middleware->replace(
            Illuminate\Http\Middleware\TrustProxies::class,
            ProcessMakerMiddleware\TrustProxies::class
        );
        $middleware->replace(
            Authenticate::class,
            ProcessMakerMiddleware\ProcessMakerAuthenticate::class
        );

        // Global middleware - custom only (Laravel defaults are already included)
        $middleware->append(ProcessMakerMiddleware\BrowserCache::class);
        $middleware->append(ProcessMakerMiddleware\ServerTimingMiddleware::class);
        $middleware->append(ProcessMakerMiddleware\FileSizeCheck::class);
        $middleware->append(ProcessMakerMiddleware\AddTenantHeaders::class);
        $middleware->append(ProcessMakerMiddleware\HideServerHeaders::class);

        // Remove CSRF middleware from web group (was disabled in original Kernel.php)
        $middleware->removeFromGroup('web', Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

        // Replace Laravel default middleware in web group
        $middleware->replaceInGroup('web',
            Illuminate\Cookie\Middleware\EncryptCookies::class,
            ProcessMakerMiddleware\EncryptCookies::class
        );
        $middleware->replaceInGroup('web',
            Illuminate\Session\Middleware\AuthenticateSession::class,
            ProcessMakerMiddleware\AuthenticateSession::class
        );

        // Set middleware priority - IgnoreMapFiles must run before Authenticate
        $middleware->priority([
            ProcessMakerMiddleware\IgnoreMapFiles::class,
            ProcessMakerMiddleware\AuthenticateSession::class,
            ProcessMakerMiddleware\ProcessMakerAuthenticate::class,
        ]);

        // Web middleware group - custom middleware only
        $middleware->appendToGroup('web', [
            ProcessMakerMiddleware\SessionStarted::class,
            ProcessMakerMiddleware\EnsureAccountAllowsAccess::class,
            ProcessMakerMiddleware\SessionControlKill::class,
            ProcessMakerMiddleware\SetLocale::class,
            ProcessMakerMiddleware\GenerateMenus::class,
            ProcessMakerMiddleware\IgnoreMapFiles::class,
            CreateFreshApiToken::class,
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
            'auth.basic' => Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'throttle' => Illuminate\Routing\Middleware\ThrottleRequests::class,
            'client' => Laravel\Passport\Http\Middleware\CheckToken::class,
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
