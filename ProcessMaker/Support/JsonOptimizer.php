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
        if (extension_loaded('simdjson') && config('app.json_optimization') === true) {
            try {
                return \SimdJson\decode($json, $assoc, $depth, $options);
            } catch (\Throwable $e) {
                Log::warning("simdjson failed: {$e->getMessage()}");
            }
        }

        return json_decode($json, $assoc, $depth, $options);
    }

    /**
     * Uses the native json_encode function (simdjson doesn't support encoding).
     */
    public static function encode(mixed $value, int $flags = 0, int $depth = 512): string|false
    {
        return json_encode($value, $flags, $depth);
    }
}
