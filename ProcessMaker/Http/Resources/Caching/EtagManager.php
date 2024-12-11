<?php

namespace ProcessMaker\Http\Resources\Caching;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EtagManager
{
    /**
     * The callback used to generate the ETag.
     */
    protected static ?Closure $etagGenerateCallback = null;

    /**
     * Set a callback that should be used when generating the ETag.
     */
    public static function etagGenerateUsing(?Closure $callback): void
    {
        static::$etagGenerateCallback = $callback;
    }

    /**
     * Get the ETag value for this request and response.
     */
    public static function getEtag(Request $request, Response $response): string
    {
        $etag = static::$etagGenerateCallback
            ? call_user_func(static::$etagGenerateCallback, $request, $response)
            : static::defaultGetEtag($response);

        return (string) Str::of($etag)->start('"')->finish('"');
    }

    /**
     * Generate a default ETag, including user-specific data by default.
     */
    private static function defaultGetEtag(Response $response): string
    {
        return md5(auth()->id() . $response->getContent());
    }

    /**
     * Generate an ETag based on the latest update timestamps from multiple tables.
     */
    public static function generateEtagFromTables(array $tables, string $source = 'updated_at'): string
    {
        // Fetch the latest update timestamp from each table.
        // If the source is 'etag_version', use a cached version key as the source of truth.
        $lastUpdated = collect($tables)->map(function ($table) use ($source) {
            if ($source === 'etag_version') {
                /**
                 * This is not currently implemented but serves as a placeholder for future flexibility.
                 * The idea is to use a cached version key (e.g., "etag_version_table_name") as the source of truth.
                 * This would allow us to version the ETag dynamically and invalidate it using model observers or other mechanisms.
                 * If implemented, observers can increment this version key whenever the corresponding table is updated.
                 */
                return Cache::get("etag_version_{$table}", 0);
            }

            // Default to the updated_at column in the database.
            return DB::table($table)->max('updated_at');
        })->max();

        $etag = md5(auth()->id() . $lastUpdated);

        return (string) Str::of($etag)->start('"')->finish('"');
    }
}
