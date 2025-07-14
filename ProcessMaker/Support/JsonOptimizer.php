<?php

namespace ProcessMaker\Support;

use Illuminate\Support\Facades\Log;

class JsonOptimizer
{
    /**
     * Global configuration set by ServiceProvider
     */
    public static bool $useSimdjsonDecode = false;

    public static bool $useSimdjsonEncode = false;

    /**
     * Decodes a JSON using simdjson if available.
     */
    public static function decode(string $json, bool $assoc = false, int $depth = 512, int $options = 0)
    {
        if (self::$useSimdjsonDecode) {
            try {
                return simdjson_decode($json, $assoc, $depth);
            } catch (\Throwable $e) {
                Log::warning("simdjson_decode failed: {$e->getMessage()}");
            }
        }

        return json_decode($json, $assoc, $depth, $options);
    }

    /**
     * Encodes a JSON using simdjson if available.
     * But json_encode is more performant than json_optimization_encode.
     * to_do: research other options for json_encode optimization.
     */
    public static function encode(mixed $value, int $flags = 0, int $depth = 512): string|false
    {
        if (self::$useSimdjsonEncode) {
            try {
                return simdjson_encode($value, $flags, $depth);
            } catch (\Throwable $e) {
                Log::warning("simdjson_encode failed: {$e->getMessage()}");
            }
        }

        return json_encode($value, $flags, $depth);
    }
}
