<?php

namespace ProcessMaker\Providers;

use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Contracts\Broadcasting\Broadcaster as BroadcasterContract;
use Illuminate\Contracts\Broadcasting\Factory as BroadcastingFactory;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;
use ProcessMaker\Multitenancy\Broadcasting\TenantAwareBroadcastManager;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (config('app.multitenancy')) {
            $this->useTenantAwareBroadcastManager();
        }

        Broadcast::routes(['middleware' => ['web', 'auth:anon']]);
        require base_path('routes/channels.php');
    }

    /**
     * Replace Laravel's deferred BroadcastManager after it has registered, so
     * channel callbacks stay on one driver instance for the life of the Octane
     * worker. Tenant prefixes are applied at auth/broadcast time instead.
     */
    private function useTenantAwareBroadcastManager(): void
    {
        $this->app->make(BroadcastManager::class);

        $manager = new TenantAwareBroadcastManager($this->app);
        $this->app->instance(BroadcastManager::class, $manager);
        $this->app->instance(BroadcastingFactory::class, $manager);
        $this->app->forgetInstance(BroadcasterContract::class);

        Broadcast::clearResolvedInstance(BroadcastManager::class);
        Broadcast::clearResolvedInstance(BroadcastingFactory::class);
    }
}
