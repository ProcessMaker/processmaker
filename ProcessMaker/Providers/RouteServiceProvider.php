<?php

namespace ProcessMaker\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

/**
 * Our Route Service Provider
 */
class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'ProcessMaker\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        Route::pattern('user', '[0-9]+');
        Route::pattern('group', '[0-9]+');
        Route::pattern('group_member', '[0-9]+');
        Route::pattern('environment_variable', '[0-9]+');
        Route::pattern('screen', '[0-9]+');
        Route::pattern('screen_category', '[0-9]+');
        Route::pattern('script', '[0-9]+');
        Route::pattern('script_id', '[0-9]+');
        Route::pattern('script_category', '[0-9]+');
        Route::pattern('process', '[0-9]+');
        Route::pattern('processId', '[0-9]+');
        Route::pattern('process_category', '[0-9]+');
        Route::pattern('task', '[0-9]+');
        Route::pattern('request', '[0-9]+');
        Route::pattern('file', '[0-9]+');
        Route::pattern('notification', '[a-zA-Z0-9-]+');
        Route::pattern('task_assignment', '[0-9]+');
        Route::pattern('comment', '[0-9]+');
        Route::pattern('script_executor', '[0-9]+');
        Route::pattern('securityLog', '[0-9]+');
        Route::pattern('setting', '[0-9]+');

        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();

        $this->mapWebRoutes();

        $this->mapEngineRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
            ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::middleware('api')
            ->group(base_path('routes/api.php'));
        Route::middleware('auth:api')
            ->group(base_path('routes/v1_1/api.php'));
    }

    /**
     * Define the "engine" routes for the application.
     *
     * These routes are typically stateless and high performant.
     *
     * @return void
     */
    protected function mapEngineRoutes()
    {
        Route::middleware('engine')
            ->group(base_path('routes/engine.php'));
    }
}
