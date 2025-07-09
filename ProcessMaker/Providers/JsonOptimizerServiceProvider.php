<?php

namespace ProcessMaker\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use ProcessMaker\Support\JsonOptimizer;

class JsonOptimizerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->initializeJsonOptimizer();
    }

    /**
     * Initialize JsonOptimizer configuration once at application startup
     */
    private function initializeJsonOptimizer(): void
    {
        // Check if simdjson_plus extension is available
        $simdjsonAvailable = extension_loaded('simdjson_plus');

        // Set optimization flags based on extension availability and config
        JsonOptimizer::$useSimdjsonDecode = $simdjsonAvailable && config('app.json_optimization_decode') === true;
        JsonOptimizer::$useSimdjsonEncode = $simdjsonAvailable && config('app.json_optimization_encode') === true;
    }
}
