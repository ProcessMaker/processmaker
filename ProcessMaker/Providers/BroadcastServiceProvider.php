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
        //auth:web,anon is needed to allow anonymous users to listen to channels
        Broadcast::routes(['middleware' => ['web', 'auth:web,anon', BroadcastAuthDebug::class]]);
        require base_path('routes/channels.php');
    }
}
