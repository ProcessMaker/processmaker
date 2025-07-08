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

        // Log the optimization status
        if ($simdjsonAvailable) {
            Log::info('SIMDJSON extension loaded', [
                'decode_optimization' => JsonOptimizer::$useSimdjsonDecode,
                'encode_optimization' => JsonOptimizer::$useSimdjsonEncode,
                'app_config_decode' => config('app.json_optimization_decode'),
                'app_config_encode' => config('app.json_optimization_encode'),
            ]);
        } else {
            Log::info('SIMDJSON extension not available - using native JSON functions');
        }
    }
}
