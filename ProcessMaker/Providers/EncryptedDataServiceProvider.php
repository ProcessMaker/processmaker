<?php

namespace ProcessMaker\Providers;

use Illuminate\Support\ServiceProvider;
use ProcessMaker\Managers\EncryptedDataManager;

class EncryptedDataServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(EncryptedDataManager::class, function () {
            return new EncryptedDataManager();
        });
    }
}
