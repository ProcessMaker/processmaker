<?php

namespace ProcessMaker\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Artisan;
use ProcessMaker\Models\ScriptExecutor;

class PhpInlineServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        config(['script-runners.php-inline' => [
            'name' => 'PHP Inline Runner',
            'system' => true,
            'runner' => 'PhpInlineRunner',
            'mime_type' => 'application/x-php',
        ]]);

        Artisan::command('docker-executor-php-inline:install', function() {
            ScriptExecutor::install([
                'language' => 'php-inline',
                'title' => 'PHP Inline Executor',
                'description' => 'Inline PHP Executor',
                'config' => ''
            ]);
        });
    }
}
