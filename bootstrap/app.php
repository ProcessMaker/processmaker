<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        \Spatie\Multitenancy\MultitenancyServiceProvider::class,
        \Laravel\Passport\PassportServiceProvider::class,
        \Laravel\Scout\ScoutServiceProvider::class,
        \TeamTNT\Scout\TNTSearchScoutServiceProvider::class,
        \Jenssegers\Agent\AgentServiceProvider::class,
        \ProcessMaker\Providers\ProcessMakerServiceProvider::class,
        \ProcessMaker\Providers\RecommendationsServiceProvider::class,
        \ProcessMaker\Providers\AuthServiceProvider::class,
        \ProcessMaker\Providers\EventServiceProvider::class,
        \ProcessMaker\Providers\HorizonServiceProvider::class,
        \ProcessMaker\Providers\TelescopeServiceProvider::class,
        \ProcessMaker\Providers\RouteServiceProvider::class,
        \ProcessMaker\Providers\BroadcastServiceProvider::class,
        \ProcessMaker\Providers\WorkflowServiceProvider::class,
        \ProcessMaker\Providers\UpgradeServiceProvider::class,
        \ProcessMaker\Providers\OauthMailServiceProvider::class,
        \ProcessMaker\Providers\OpenAiServiceProvider::class,
        \ProcessMaker\Providers\LicenseServiceProvider::class,
        \ProcessMaker\Providers\MetricsServiceProvider::class,
        \ProcessMaker\Providers\MixServiceProvider::class,
        \ProcessMaker\Providers\TenantQueueServiceProvider::class,
        \ProcessMaker\Providers\JsonOptimizerServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        // channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(RouteServiceProvider::HOME);

        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
