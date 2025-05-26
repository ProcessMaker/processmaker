<?php

namespace ProcessMaker\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Broadcast::routes(['middleware'=>['web', 'auth:anon']]);
        Broadcast::routes([
            'middleware' => ['auth:api'],
            'prefix'     => 'api',
        ]);

        require base_path('routes/channels.php');
    }
}
