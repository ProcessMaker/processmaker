<?php

namespace ProcessMaker\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

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
        // Only optimize in production or when explicitly enabled
        if (config('app.json_optimization') === true) {
            $this->optimizeJsonFunctions();
        }
    }

    /**
     * Optimize JSON functions using SIMDJSON and UOPZ
     */
    private function optimizeJsonFunctions()
    {
        // Check if required extensions are loaded
        if (!extension_loaded('simdjson')) {
            Log::info('SIMDJSON extension not loaded. Using native JSON functions.');

            return;
        }

        if (!extension_loaded('uopz')) {
            Log::info('UOPZ extension not loaded. Using native JSON functions.');

            return;
        }

        try {
            // Store original functions
            $originalJsonDecode = 'json_decode';
            $originalJsonEncode = 'json_encode';

            // Rename original functions to preserve them
            uopz_rename('json_decode', 'php_json_decode');
            uopz_rename('json_encode', 'php_json_encode');

            // Create optimized json_decode
            uopz_function('json_decode', function ($json, $assoc = false, $depth = 512, $options = 0) {
                try {
                    return simdjson_decode($json, $assoc, $depth, $options);
                } catch (\Exception $e) {
                    // Fallback to original if SIMDJSON fails
                    return php_json_decode($json, $assoc, $depth, $options);
                }
            });

            // Create optimized json_encode
            uopz_function('json_encode', function ($value, $options = 0, $depth = 512) {
                try {
                    return simdjson_encode($value, $options, $depth);
                } catch (\Exception $e) {
                    // Fallback to original if SIMDJSON fails
                    return php_json_encode($value, $options, $depth);
                }
            });

            Log::info('JSON optimization enabled: SIMDJSON + UOPZ');
        } catch (\Exception $e) {
            Log::error('Failed to optimize JSON functions: ' . $e->getMessage());
        }
    }
}
