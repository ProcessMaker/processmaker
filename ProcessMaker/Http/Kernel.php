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
        Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        Middleware\TrustProxies::class,
        Middleware\BrowserCache::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            Middleware\EncryptCookies::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            Middleware\SessionStarted::class,
            Middleware\AuthenticateSession::class,
            Middleware\SessionControlKill::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            //\ProcessMaker\Http\Middleware\VerifyCsrfToken::class,
            Middleware\SetLocale::class,       // This is disabled until all routes are handled by our new engine
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            Middleware\GenerateMenus::class,
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
        'auth' => Middleware\ProcessMakerAuthenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'can' => Middleware\CustomAuthorize::class,
        'force_change_password' => Middleware\VerifyChangePasswordNeeded::class,
        'guest' => Middleware\RedirectIfAuthenticated::class,
        'permission' => Middleware\PermissionCheck::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'sanitize' => Middleware\SanitizeInput::class,
        'setlocale' => Middleware\SetLocale::class,
        'setskin' => Middleware\SetSkin::class,
        'client' => \Laravel\Passport\Http\Middleware\CheckClientCredentials::class,
        'template-authorization' => Middleware\TemplateAuthorization::class,
        'edit_username_password' => Middleware\ValidateEditUserAndPasswordPermission::class,
        '2fa' => Middleware\TwoFactorAuthentication::class,
        'saml_request' => Middleware\SamlRequest::class,
        'session_block' => Middleware\SessionControlBlock::class,
        'session_kill' => Middleware\SessionControlKill::class,
        'no-cache' => Middleware\NoCache::class,
        'admin' => Middleware\IsAdmin::class,
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
        Middleware\ProcessMakerAuthenticate::class,
    ];
}
