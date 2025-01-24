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
    public function boot(): void
    {
        Broadcast::routes(['middleware'=>['web', 'auth:anon']]);
        require base_path('routes/channels.php');
    }
}
