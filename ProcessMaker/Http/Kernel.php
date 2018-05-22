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
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
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

        ],
        'api' => [
            'throttle:60,1'
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
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \ProcessMaker\Http\Middleware\RedirectIfAuthenticated::class,
        'permission' => \ProcessMaker\Http\Middleware\PermissionCheck::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'setlang' => \ProcessMaker\Http\Middleware\SetLanguage::class,
        'setskin' => \ProcessMaker\Http\Middleware\SetSkin::class,
        'session' => \Illuminate\Session\Middleware\StartSession::class,
        'apitoken' => \ProcessMaker\Http\Middleware\GenerateApiToken::class
    ];
}
