<?php

namespace ProcessMaker\Support;

use Illuminate\Support\Facades\Log;

class JsonOptimizer
{
    /**
     * Decodes a JSON using simdjson if available.
     */
    public static function decode(string $json, bool $assoc = false, int $depth = 512, int $options = 0)
    {
        if (extension_loaded('simdjson_plus') && config('app.json_optimization_decode') === true) {
            try {
                return simdjson_decode($json, $assoc, $depth, $options);
            } catch (\Throwable $e) {
                Log::warning("simdjson_decode failed: {$e->getMessage()}");
            }
        }

        return json_decode($json, $assoc, $depth, $options);
    }

    /**
     * Encodes a JSON using simdjson if available.
     */
    public static function encode(mixed $value, int $flags = 0, int $depth = 512): string|false
    {
        if (extension_loaded('simdjson_plus') && config('app.json_optimization_encode') === true) {
            try {
                return simdjson_encode($value, $flags, $depth);
            } catch (\Throwable $e) {
                Log::warning("simdjson_encode failed: {$e->getMessage()}");
            }
        }

        return json_encode($value, $flags, $depth);
    }
}
