<?php

namespace ProcessMaker\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \ProcessMaker\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \ProcessMaker\Http\Middleware\TrustProxies::class,
        \ProcessMaker\Http\Middleware\BrowserCache::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \ProcessMaker\Http\Middleware\EncryptCookies::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \ProcessMaker\Http\Middleware\SessionStarted::class,
            \ProcessMaker\Http\Middleware\AuthenticateSession::class,
            \ProcessMaker\Http\Middleware\SessionControlKill::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            //\ProcessMaker\Http\Middleware\VerifyCsrfToken::class,
            \ProcessMaker\Http\Middleware\SetLocale::class,       // This is disabled until all routes are handled by our new engine
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \ProcessMaker\Http\Middleware\GenerateMenus::class,
            \Laravel\Passport\Http\Middleware\CreateFreshApiToken::class,
        ],
        'api' => [
            // API Middleware is defined with routeMiddleware below.
            // See routes/api.php
        ],
        'engine' => [
            'auth:api',
            'setlocale',
            'bindings',
            'sanitize',
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => \ProcessMaker\Http\Middleware\ProcessMakerAuthenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'can' => \ProcessMaker\Http\Middleware\CustomAuthorize::class,
        'force_change_password' => \ProcessMaker\Http\Middleware\VerifyChangePasswordNeeded::class,
        'guest' => \ProcessMaker\Http\Middleware\RedirectIfAuthenticated::class,
        'permission' => \ProcessMaker\Http\Middleware\PermissionCheck::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'sanitize' => \ProcessMaker\Http\Middleware\SanitizeInput::class,
        'setlocale' => \ProcessMaker\Http\Middleware\SetLocale::class,
        'setskin' => \ProcessMaker\Http\Middleware\SetSkin::class,
        'client' => \Laravel\Passport\Http\Middleware\CheckClientCredentials::class,
        'template-authorization' => \ProcessMaker\Http\Middleware\TemplateAuthorization::class,
        'edit_username_password' => \ProcessMaker\Http\Middleware\ValidateEditUserAndPasswordPermission::class,
        '2fa' => \ProcessMaker\Http\Middleware\TwoFactorAuthentication::class,
        'saml_request' => \ProcessMaker\Http\Middleware\SamlRequest::class,
        'session_block' => \ProcessMaker\Http\Middleware\SessionControlBlock::class,
        'session_kill' => \ProcessMaker\Http\Middleware\SessionControlKill::class,
        'no-cache' => \ProcessMaker\Http\Middleware\NoCache::class,
    ];

    /**
     * The auth:anon middleware must run after a session is set up to
     * check if there is a user logged in before implying the user is
     * anonymous.
     *
     * The auth:anon middleware is only used for the laravel echo
     * server route: broadcasting/auth
     *
     * @var array
     */
    protected $middlewarePriority = [
        \Illuminate\Session\Middleware\AuthenticateSession::class,
        \ProcessMaker\Http\Middleware\ProcessMakerAuthenticate::class,
    ];
}
