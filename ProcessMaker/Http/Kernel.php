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
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \ProcessMaker\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \ProcessMaker\Http\Middleware\TrustProxies::class,
    ];
    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \ProcessMaker\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,   // In case we want to log users out after changing password, we need this
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            //\ProcessMaker\Http\Middleware\VerifyCsrfToken::class,         // This is disabled until all routes are handled by our new engine
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \ProcessMaker\Http\Middleware\GenerateMenus::class,
            \Laravel\Passport\Http\Middleware\CreateFreshApiToken::class,


        ],
        'api' => [
            // Empty middleware for api
            // @todo Determine if we need throttling.  Currently it interrupts test suites
            // However, we haven't had a product decision on utilizing throttling or not
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
        'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'authorize' => \ProcessMaker\Http\Middleware\Authorize::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        // 'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \ProcessMaker\Http\Middleware\RedirectIfAuthenticated::class,
        'permission' => \ProcessMaker\Http\Middleware\PermissionCheck::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'setlang' => \ProcessMaker\Http\Middleware\SetLanguage::class,
        'setskin' => \ProcessMaker\Http\Middleware\SetSkin::class,
        'session' => \Illuminate\Session\Middleware\StartSession::class,
    ];
}
