<?php

namespace ProcessMaker\Providers;

use Illuminate\Foundation\Mix;
use Illuminate\Support\ServiceProvider;
use ProcessMaker\Support\MultitenantMix;

class MixServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(Mix::class, MultitenantMix::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}