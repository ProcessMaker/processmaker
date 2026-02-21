<?php

namespace ProcessMaker\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;
use ProcessMaker\Http\Middleware\BroadcastAuthDebug;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Broadcast::routes(['middleware' => ['web', 'auth:web,anon', BroadcastAuthDebug::class]]);
        require base_path('routes/channels.php');
    }
}
